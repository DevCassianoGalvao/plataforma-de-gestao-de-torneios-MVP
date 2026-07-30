<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\RegistrationSeed;
use App\Repositories\AthleteDocumentRepository;
use App\Repositories\AthleteRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\RegulationRepository;
use App\Repositories\RegistrationRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\RegistrationAccessService;
use App\Services\RegistrationService;
use function Tests\assert_same;
use function Tests\assert_true;

final class RegistrationIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        RegistrationSeed::run($pdo);
        RegistrationSeed::run($pdo);
        assert_same(10, (int) $pdo->query('SELECT COUNT(*) FROM athlete_registrations')->fetchColumn(), 'Seed duplicou inscricoes');
        assert_true((int) $pdo->query('SELECT COUNT(*) FROM athlete_registration_history')->fetchColumn() >= 20, 'Historico inicial de inscricoes ausente');
        assert_true((int) $pdo->query('SELECT COUNT(*) FROM regulation_roster_settings')->fetchColumn() >= 1, 'Configuracao de elenco ausente');
        assert_true((int) $pdo->query('SELECT COUNT(*) FROM regulation_required_documents')->fetchColumn() >= 1, 'Documento obrigatorio ausente');

        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $organizer = $users->findByEmail('organizador@torneios.local');
        $trainer = $users->findByEmail('treinador@torneios.local');
        $outsider = $users->findByEmail('treinador-sem-equipe@torneios.local');
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026'")->fetchColumn();
        $teamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'estrela-norte-fc'")->fetchColumn();
        $foreignTeamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'serra-azul-futebol'")->fetchColumn();
        $athleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$teamId} ORDER BY id LIMIT 1 OFFSET 1")->fetchColumn();
        $repository = new RegistrationRepository($pdo);
        $access = new RegistrationAccessService($repository, new TeamRepository($pdo), new ChampionshipRepository($pdo), new AuthorizationService($users));
        assert_same(10, count($access->list($admin)), 'Administrador nao ve inscricoes');
        assert_same(5, count($access->list($trainer)), 'Treinador recebeu inscricoes de outra equipe');
        assert_same(0, count($access->list($outsider)), 'Treinador sem equipe recebeu inscricoes');
        $foreignRegistrationId = (int) $pdo->query("SELECT id FROM athlete_registrations WHERE team_id = {$foreignTeamId} LIMIT 1")->fetchColumn();
        assert_true($repository->findForUser($foreignRegistrationId, (int) $trainer['id'], 'team') === null, 'Treinador acessou inscricao IDOR');
        assert_true($repository->hasOtherTeamRegistration($championshipId, (int) $pdo->query("SELECT athlete_id FROM athlete_registrations WHERE team_id = {$teamId} LIMIT 1")->fetchColumn(), $foreignTeamId), 'Duplicidade por outra equipe nao identificada');

        $service = new RegistrationService($repository, new ChampionshipRepository($pdo), new TeamRepository($pdo), new AthleteRepository($pdo), new AthleteDocumentRepository($pdo), new RegulationRepository($pdo), new AuditService($pdo));
        $created = $service->createDraft((int) $trainer['id'], $championshipId, $teamId, $athleteId, 88, 'Fluxo de integracao');
        assert_true($created['ok'] === true, 'Rascunho de inscricao nao foi criado');
        $registrationId = (int) $created['id'];
        $registration = $repository->findForUser($registrationId, (int) $trainer['id'], 'team');
        assert_true($registration !== null, 'Rascunho nao ficou no escopo do treinador');
        assert_true($service->submit($registration, (int) $trainer['id'])['ok'] === true, 'Envio de inscricao falhou');
        $registration = $repository->findByPair($championshipId, $teamId, $athleteId);
        assert_true($service->startReview($registration, (int) $organizer['id'])['ok'] === true, 'Inicio de analise falhou');
        $registration = $repository->findByPair($championshipId, $teamId, $athleteId);
        assert_true($service->requestCorrection($registration, (int) $organizer['id'], 'Atualize observacao')['ok'] === true, 'Pendencia nao foi registrada');
        $registration = $repository->findByPair($championshipId, $teamId, $athleteId);
        assert_true($service->updateDraft($registration, (int) $trainer['id'], 88, 'Correcao enviada')['ok'] === true, 'Correcao nao foi persistida');
        $registration = $repository->findByPair($championshipId, $teamId, $athleteId);
        assert_true($service->submit($registration, (int) $trainer['id'])['ok'] === true, 'Reenvio apos correcao falhou');
        $registration = $repository->findByPair($championshipId, $teamId, $athleteId);
        assert_true($service->startReview($registration, (int) $organizer['id'])['ok'] === true, 'Reanalise falhou');
        $registration = $repository->findByPair($championshipId, $teamId, $athleteId);
        assert_true($service->approve($registration, (int) $organizer['id'])['ok'] === true, 'Aprovacao falhou');
        assert_true(count($service->history($registrationId)) >= 8, 'Correcao ou transicao sem historico');
        assert_true(count($service->officialRoster($championshipId, $teamId)) >= 2, 'Elenco oficial nao incluiu aprovado');

        $adultCategoryId = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'adulto-masculino' LIMIT 1")->fetchColumn();
        $originalCategoryId = (int) $pdo->query("SELECT category_id FROM championships WHERE id = {$championshipId}")->fetchColumn();
        $adultTeamId = (int) $pdo->query("SELECT id FROM teams WHERE id NOT IN ({$teamId}, {$foreignTeamId}) ORDER BY id LIMIT 1")->fetchColumn();
        $adultAthleteId = (int) $pdo->query("SELECT a.id FROM athletes a WHERE a.team_id = {$adultTeamId} AND a.id NOT IN (SELECT athlete_id FROM athlete_registrations WHERE championship_id = {$championshipId}) ORDER BY a.id LIMIT 1")->fetchColumn();
        $originalBirthDate = (string) $pdo->query("SELECT birth_date FROM athletes WHERE id = {$adultAthleteId}")->fetchColumn();
        $pdo->prepare('UPDATE championships SET category_id = ? WHERE id = ?')->execute([$adultCategoryId, $championshipId]);
        $pdo->prepare("UPDATE athletes SET birth_date = '2000-01-01' WHERE id = ?")->execute([$adultAthleteId]);
        $adultDraft = $service->createDraft((int) $admin['id'], $championshipId, $adultTeamId, $adultAthleteId, 89, 'Adulto sem autorizacao de responsavel');
        assert_true($adultDraft['ok'] === true, 'Rascunho adulto nao foi criado');
        $adultRegistration = $repository->findByPair($championshipId, $adultTeamId, $adultAthleteId);
        assert_true($service->submit($adultRegistration, (int) $admin['id'])['ok'] === true, 'Adulto foi bloqueado por autorizacao de responsavel');
        $adultRegistration = $repository->findByPair($championshipId, $adultTeamId, $adultAthleteId);
        $service->cancel($adultRegistration, (int) $admin['id']);
        $pdo->prepare('DELETE FROM athlete_registration_history WHERE registration_id = ?')->execute([(int) $adultRegistration['id']]);
        $pdo->prepare('DELETE FROM athlete_registrations WHERE id = ?')->execute([(int) $adultRegistration['id']]);
        $pdo->prepare('UPDATE championships SET category_id = ? WHERE id = ?')->execute([$originalCategoryId, $championshipId]);
        $pdo->prepare('UPDATE athletes SET birth_date = ? WHERE id = ?')->execute([$originalBirthDate, $adultAthleteId]);

        self::assertSubmissionIssue($pdo, $service, $repository, $championshipId, $foreignTeamId, 'period', function () use ($pdo): void {
            $pdo->exec("UPDATE championships SET registration_ends_at = '2020-01-01' LIMIT 1");
        }, function () use ($pdo): void {
            $pdo->exec("UPDATE championships SET registration_ends_at = '2026-12-31' LIMIT 1");
        });
        $ageTeamId = (int) $pdo->query("SELECT id FROM teams WHERE id NOT IN ({$teamId}, {$foreignTeamId}, {$adultTeamId}) ORDER BY id LIMIT 1")->fetchColumn();
        self::assertSubmissionIssue($pdo, $service, $repository, $championshipId, $ageTeamId, 'idade', function () use ($pdo, $ageTeamId): void {
            $pdo->exec("UPDATE athletes SET birth_date = '2000-01-01' WHERE team_id = {$ageTeamId}");
        }, function () use ($pdo, $ageTeamId): void {
            $pdo->exec("UPDATE athletes SET birth_date = '2012-04-01' WHERE team_id = {$ageTeamId}");
        });
        $documentTeamId = (int) $pdo->query("SELECT id FROM teams WHERE id NOT IN ({$teamId}, {$foreignTeamId}, {$adultTeamId}, {$ageTeamId}) ORDER BY id LIMIT 1")->fetchColumn();
        self::assertSubmissionIssue($pdo, $service, $repository, $championshipId, $documentTeamId, 'documento', static function (): void {}, static function (): void {});
        $limitTeamId = (int) $pdo->query("SELECT t.id FROM teams t WHERE t.id NOT IN ({$teamId}, {$foreignTeamId}, {$ageTeamId}, {$documentTeamId}) AND EXISTS (SELECT 1 FROM athlete_registrations ar0 WHERE ar0.team_id = t.id AND ar0.status = 'approved') ORDER BY t.id LIMIT 1")->fetchColumn();
        $limitAthleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$limitTeamId} AND id NOT IN (SELECT athlete_id FROM athlete_registrations WHERE team_id = {$limitTeamId}) ORDER BY id LIMIT 1")->fetchColumn();
        $limitDocumentId = (int) $pdo->query("SELECT d.id FROM athlete_documents d INNER JOIN athlete_document_types dt ON dt.id = d.document_type_id WHERE d.athlete_id = {$limitAthleteId} AND dt.`key` = 'guardian_authorization' LIMIT 1")->fetchColumn();
        $limitDocumentStatus = (string) $pdo->query("SELECT status FROM athlete_documents WHERE id = {$limitDocumentId}")->fetchColumn();
        $regulationId = (int) $pdo->query("SELECT id FROM regulations WHERE championship_id = {$championshipId} AND status = 'published' LIMIT 1")->fetchColumn();
        $pdo->prepare("UPDATE regulation_roster_settings SET maximum_roster_size = 1 WHERE regulation_id = ?")->execute([$regulationId]);
        $pdo->prepare("UPDATE athlete_documents SET status = 'approved', rejection_reason = NULL WHERE id = ?")->execute([$limitDocumentId]);
        $limitDraft = $service->createDraft((int) $admin['id'], $championshipId, $limitTeamId, $limitAthleteId, 97, 'Limite');
        assert_true($limitDraft['ok'] === true, 'Rascunho de limite nao foi criado');
        $limitRegistration = $repository->findByPair($championshipId, $limitTeamId, $limitAthleteId);
        assert_true($service->submit($limitRegistration, (int) $admin['id'])['ok'] === true, 'Inscricao de limite nao foi enviada');
        $limitRegistration = $repository->findByPair($championshipId, $limitTeamId, $limitAthleteId);
        assert_true($service->startReview($limitRegistration, (int) $admin['id'])['ok'] === true, 'Inscricao de limite nao entrou em analise');
        $limitRegistration = $repository->findByPair($championshipId, $limitTeamId, $limitAthleteId);
        $limitResult = $service->approve($limitRegistration, (int) $admin['id']);
        assert_true($limitResult['ok'] === false && str_contains(strtolower(implode(' ', $limitResult['errors'])), 'limite'), 'Limite maximo de elenco nao bloqueou aprovacao');
        $service->reject($limitRegistration, (int) $admin['id'], 'Teste de limite');
        $pdo->prepare("UPDATE regulation_roster_settings SET maximum_roster_size = 25 WHERE regulation_id = ?")->execute([$regulationId]);
        $pdo->prepare("UPDATE athlete_documents SET status = ?, rejection_reason = NULL WHERE id = ?")->execute([$limitDocumentStatus, $limitDocumentId]);
        assert_true($service->rosterIssues($championshipId, $teamId) === [], 'Elenco valido recebeu bloqueio');
    }

    private static function assertSubmissionIssue($pdo, RegistrationService $service, RegistrationRepository $repository, int $championshipId, int $teamId, string $needle, callable $before, callable $after): void
    {
        $before();
        $athleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$teamId} AND id NOT IN (SELECT athlete_id FROM athlete_registrations WHERE team_id = {$teamId}) ORDER BY id LIMIT 1")->fetchColumn();
        $users = new UserRepository($pdo);
        $adminId = (int) $users->findByEmail('admin@torneios.local')['id'];
        $created = $service->createDraft($adminId, $championshipId, $teamId, $athleteId, 77, 'Validacao');
        assert_true($created['ok'] === true, 'Rascunho para validacao nao foi criado: ' . implode(' | ', $created['errors'] ?? []) . ' team=' . $teamId . ' athlete=' . $athleteId);
        $registration = $repository->findByPair($championshipId, $teamId, $athleteId);
        $result = $service->submit($registration, $adminId);
        assert_true($result['ok'] === false && implode(' ', $result['errors']) !== '' && str_contains(strtolower(implode(' ', $result['errors'])), $needle), 'Regra ' . $needle . ' nao bloqueou envio: ' . implode(' | ', $result['errors']));
        $service->cancel($registration, $adminId);
        $after();
    }
}
