<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\TransferSeed;
use App\Repositories\TransferRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\TransferAccessService;
use App\Services\TransferService;
use function Tests\assert_same;
use function Tests\assert_true;

final class TransferIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection(); TransferSeed::run($pdo); $before = (int) $pdo->query('SELECT COUNT(*) FROM transfer_movements')->fetchColumn(); TransferSeed::run($pdo); assert_same($before, (int) $pdo->query('SELECT COUNT(*) FROM transfer_movements')->fetchColumn(), 'Seed de transferencias nao foi idempotente');
        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $trainer = $users->findByEmail('treinador@torneios.local');
        $manager = $users->findByEmail('gestor@torneios.local');
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026'")->fetchColumn();
        $repository = new TransferRepository($pdo);
        $access = new TransferAccessService($repository, new AuthorizationService($users));
        assert_true($access->canManageChampionship($admin, $championshipId), 'Administrador sem escopo de transferencias');

        $published = $repository->listPublic($championshipId); assert_true(count($published) >= 1, 'Movimento publicado nao ficou publico'); $movement = $repository->find((int) $published[0]['id']); $teamBefore = (int) $pdo->query('SELECT team_id FROM athletes WHERE id = ' . (int) $movement['athlete_id'])->fetchColumn(); $service = new TransferService($repository, new AuditService($pdo), $access); assert_true($service->transition((int) $movement['id'], (int) $admin['id'], 'cancelled', 'cancelled', 'Correcao'), 'Cancelamento falhou'); $teamAfter = (int) $pdo->query('SELECT team_id FROM athletes WHERE id = ' . (int) $movement['athlete_id'])->fetchColumn(); assert_same($teamBefore, $teamAfter, 'Aprovacao/publicacao alterou vinculo oficial'); assert_true(count($repository->history((int) $movement['id'])) >= 2, 'Historico de transferencia ausente');
        $private = $repository->find((int) $repository->listAdmin(null)[0]['id']); assert_true(str_contains((string) $private['internal_notes'], 'Nota interna'), 'Nota interna nao foi armazenada'); assert_true(!array_key_exists('internal_notes', $published[0]), 'Consulta publica vazou campo privado');

        $trainerTeamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'estrela-norte-fc'")->fetchColumn();
        $managerTeamId = (int) $pdo->query("SELECT id FROM teams WHERE slug = 'serra-azul-futebol'")->fetchColumn();
        assert_true($access->canRequestForTeam($trainer, $trainerTeamId), 'Treinador nao pode solicitar pela propria equipe');
        assert_true(!$access->canRequestForTeam($trainer, $managerTeamId), 'Treinador pode solicitar por equipe alheia');

        $destinationTeamId = (int) $pdo->query("SELECT id FROM teams WHERE championship_id = {$championshipId} AND id <> {$trainerTeamId} ORDER BY id LIMIT 1")->fetchColumn();
        $requestAthleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$trainerTeamId} AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        $date = date('Y-m-d', strtotime('+3 days'));
        $created = $service->save($trainer, ['championship_id' => $championshipId, 'athlete_id' => $requestAthleteId, 'previous_team_id' => $trainerTeamId, 'new_team_id' => $destinationTeamId, 'type' => 'transferencia', 'movement_date' => $date, 'status' => 'pending'], null);
        assert_true($created['ok'], 'Treinador nao criou solicitacao da propria equipe: ' . implode(' | ', $created['errors'] ?? []));
        $requestId = (int) $created['id']; $request = $repository->find($requestId);
        assert_true($access->canAccessRecord($trainer, $request), 'Treinador nao acessa a propria solicitacao');
        assert_true($access->canCancelRecord($trainer, $request), 'Treinador nao pode cancelar a propria solicitacao pendente');
        assert_true(!$access->canCancelRecord($manager, $request), 'Outro treinador pode cancelar solicitacao alheia');

        $foreignAthleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$managerTeamId} AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        $invalidOrigin = $service->save($trainer, ['championship_id' => $championshipId, 'athlete_id' => $foreignAthleteId, 'previous_team_id' => $managerTeamId, 'new_team_id' => $trainerTeamId, 'type' => 'transferencia', 'movement_date' => $date, 'status' => 'pending'], null);
        assert_true(!$invalidOrigin['ok'], 'Treinador criou solicitacao a partir de equipe alheia');

        assert_true($service->transition($requestId, (int) $trainer['id'], 'cancelled', 'cancelled', 'Desistencia'), 'Treinador nao cancelou a propria solicitacao pendente');
        assert_same('cancelled', (string) $repository->find($requestId)['status'], 'Status de cancelamento nao persistiu');

        $secondAthleteId = (int) $pdo->query("SELECT id FROM athletes WHERE team_id = {$trainerTeamId} AND id <> {$requestAthleteId} AND deleted_at IS NULL ORDER BY id LIMIT 1")->fetchColumn();
        $officialFlow = $service->save($admin, ['championship_id' => $championshipId, 'athlete_id' => $secondAthleteId, 'previous_team_id' => $trainerTeamId, 'new_team_id' => $destinationTeamId, 'type' => 'transferencia', 'movement_date' => $date, 'status' => 'pending'], null);
        assert_true($officialFlow['ok'], 'Administrador nao criou movimentacao para o fluxo oficial');
        $officialId = (int) $officialFlow['id'];
        assert_true($service->transition($officialId, (int) $admin['id'], 'approved', 'approved', null), 'Aprovacao administrativa falhou');
        assert_true($service->transition($officialId, (int) $admin['id'], 'published', 'published', null), 'Publicacao administrativa falhou');
        assert_true($service->applyOfficial($officialId, (int) $admin['id']), 'Aplicacao do vinculo oficial falhou');
        assert_same($destinationTeamId, (int) $pdo->query('SELECT team_id FROM athletes WHERE id = ' . $secondAthleteId)->fetchColumn(), 'Vinculo oficial do atleta nao foi atualizado');
    }
}
