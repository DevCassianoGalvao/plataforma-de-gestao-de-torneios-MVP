<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Database\ScheduleSeed;
use App\Repositories\ChampionshipRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\ScheduleAccessService;
use App\Services\ScheduleService;
use function Tests\assert_same;
use function Tests\assert_true;

final class ScheduleIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        ScheduleSeed::run($pdo);
        ScheduleSeed::run($pdo);
        assert_same(1, (int) $pdo->query('SELECT COUNT(*) FROM competition_phases')->fetchColumn(), 'Seed duplicou fases');
        assert_same(2, (int) $pdo->query('SELECT COUNT(*) FROM competition_groups')->fetchColumn(), 'Seed duplicou grupos');
        assert_same(10, (int) $pdo->query('SELECT COUNT(*) FROM group_teams WHERE status = \'active\'')->fetchColumn(), 'Seed nao distribuiu dez equipes');
        assert_same(10, (int) $pdo->query('SELECT COUNT(*) FROM competition_rounds')->fetchColumn(), 'Seed nao criou rodadas esperadas');
        assert_same(20, (int) $pdo->query('SELECT COUNT(*) FROM matches')->fetchColumn(), 'Seed nao criou tabela esperada');
        assert_same(20, (int) $pdo->query('SELECT COUNT(DISTINCT fixture_key) FROM matches')->fetchColumn(), 'Tabela possui fixture duplicada');
        assert_same(0, (int) $pdo->query('SELECT COUNT(*) FROM matches WHERE home_team_id = away_team_id')->fetchColumn(), 'Tabela possui equipe contra si mesma');

        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $trainer = $users->findByEmail('treinador@torneios.local');
        $outsider = $users->findByEmail('treinador-sem-equipe@torneios.local');
        $operator = $users->findByEmail('operador@torneios.local');
        $championshipId = (int) $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026'")->fetchColumn();
        $phase = $pdo->query("SELECT * FROM competition_phases WHERE slug = 'fase-grupos'")->fetch();
        $groups = $pdo->query('SELECT * FROM competition_groups ORDER BY code')->fetchAll();
        $repository = new ScheduleRepository($pdo);
        $authorization = new AuthorizationService($users);
        $access = new ScheduleAccessService($repository, new ChampionshipRepository($pdo), new TeamRepository($pdo), $authorization);
        assert_same(20, count($access->listMatches($admin)), 'Administrador nao ve tabela completa');
        assert_true(count($access->listMatches($trainer)) > 0, 'Treinador nao ve jogos da propria equipe');
        assert_same(0, count($access->listMatches($outsider)), 'Treinador sem equipe recebeu jogos');
        assert_same(0, count($access->listMatches($operator)), 'Operador recebeu escopo indevido');
        $foreign = (int) $pdo->query('SELECT id FROM matches WHERE home_team_id NOT IN (SELECT team_id FROM team_user_assignments WHERE user_id = ' . (int) $trainer['id'] . ') LIMIT 1')->fetchColumn();
        assert_true($access->findMatch($trainer, $foreign) === null, 'IDOR de partida aceito');

        $service = new ScheduleService($repository, new ChampionshipRepository($pdo), new TeamRepository($pdo), new AuditService($pdo));
        $venues = $repository->listVenues($championshipId);
        $config = ['phase_id' => (int) $phase['id'], 'group_ids' => array_map('intval', array_column($groups, 'id')), 'return_leg' => false, 'period_start' => '2026-09-01', 'period_end' => '2026-10-15', 'allowed_days' => [1, 2, 3, 4, 5, 6, 7], 'start_time' => '18:00', 'slot_minutes' => 120, 'venue_ids' => array_map('intval', array_column($venues, 'id'))];
        $preview = $service->preview((int) $admin['id'], $phase, $config);
        assert_true($preview['ok'] === true && $preview['conflicts'] === [] && count($preview['matches']) === 20, 'Previa idempotente ou sem conflitos falhou');
        assert_true($service->generate((int) $admin['id'], $phase, $config)['ok'] === true, 'Geracao idempotente falhou');
        assert_same(20, (int) $pdo->query('SELECT COUNT(*) FROM matches')->fetchColumn(), 'Geracao duplicou partidas');

        $match = $repository->listMatches((int) $admin['id'], 'administrator', ['upcoming' => '1'])[0] ?? $repository->listMatches((int) $admin['id'], 'administrator')[0];
        $changed = $service->changeAgenda((int) $admin['id'], $match, ['match_date' => '2026-10-20', 'match_time' => '18:00', 'venue_id' => (int) $venues[0]['id'], 'status' => 'postponed', 'reason' => 'Conflito de calendario'], 'postpone');
        assert_true($changed['ok'] === true, 'Adiamento nao persistiu');
        $updated = $repository->matchForUser((int) $match['id'], (int) $admin['id'], 'administrator');
        assert_same('postponed', $updated['status'], 'Status de adiamento incorreto');
        assert_same(1, count($repository->scheduleChanges((int) $match['id'])), 'Historico de agenda ausente');
        assert_true($service->changeStatus((int) $admin['id'], $updated, 'confirmed')['ok'] === true, 'Confirmacao apos adiamento falhou');
        assert_true($service->addDecision((int) $admin['id'], $updated, ['decision_type' => 'schedule', 'notes' => 'Decisao de teste'])['ok'] === true, 'Decisao administrativa falhou');
        $source = $repository->group((int) $groups[0]['id']);
        $target = $repository->group((int) $groups[1]['id']);
        $teamId = (int) $pdo->query('SELECT team_id FROM group_teams WHERE group_id = ' . (int) $source['id'] . ' LIMIT 1')->fetchColumn();
        assert_true($service->moveTeam((int) $admin['id'], $source, $target, $teamId, 6)['ok'] === false, 'Equipe duplicada entre grupos foi aceita');
        assert_true($service->startPhase((int) $admin['id'], $phase)['ok'] === true, 'Inicio de fase falhou');
        $source = $repository->group((int) $source['id']);
        assert_true($service->withdrawTeam((int) $admin['id'], $source, $teamId)['ok'] === false, 'Retirada apos inicio foi aceita');
    }
}
