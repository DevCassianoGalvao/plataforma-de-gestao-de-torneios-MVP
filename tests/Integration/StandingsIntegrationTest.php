<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Repositories\StandingsRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\StandingsService;
use PDO;
use function Tests\assert_same;
use function Tests\assert_true;

final class StandingsIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        $phase = $pdo->query("SELECT * FROM competition_phases WHERE phase_type = 'groups' ORDER BY id LIMIT 1")->fetch();
        assert_true((bool) $phase && (bool) $admin, 'Dados base da classificacao ausentes');
        $adminId = (int) $admin['id'];
        $groupIds = array_map('intval', $pdo->query('SELECT id FROM competition_groups WHERE phase_id = ' . (int) $phase['id'] . ' ORDER BY display_order')->fetchAll(PDO::FETCH_COLUMN));
        assert_same(2, count($groupIds), 'Preset nao possui dois grupos');

        foreach ($groupIds as $groupId) {
            $matches = $pdo->query('SELECT id FROM matches WHERE group_id = ' . $groupId . ' ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($matches as $index => $matchId) self::administrativeResult($pdo, (int) $matchId, $adminId, $index === 0 ? 2 : 1, $index === 0 ? 0 : 1);
        }

        $repository = new StandingsRepository($pdo);
        $service = new StandingsService($repository, new AuditService($pdo));
        $first = $service->recalculate($phase, $adminId);
        assert_true($first['ok'], 'Recalculo da classificacao falhou');
        $standingsCount = (int) $pdo->query('SELECT COUNT(*) FROM competition_standings')->fetchColumn();
        assert_same(10, $standingsCount, 'Classificacao nao criou uma linha por equipe');
        $criteria = $repository->regulation((int) $phase['championship_id'])['tiebreakers'];
        assert_same(['wins', 'goal_difference', 'goals_scored', 'head_to_head', 'fewer_cards', 'administrative_decision', 'draw_lots'], array_column($criteria, 'criterion'), 'Desempates do regulamento nao foram carregados');
        $service->recalculate($phase, $adminId);
        assert_same($standingsCount, (int) $pdo->query('SELECT COUNT(*) FROM competition_standings')->fetchColumn(), 'Recalculo duplicou classificacao');
        assert_same(2, (int) $pdo->query('SELECT COUNT(*) FROM standings_calculation_runs')->fetchColumn(), 'Hash de calculo nao tornou execucao idempotente');

        $generated = $service->generateKnockout($phase, $adminId);
        assert_true($generated['ok'], 'Geracao do mata-mata falhou');
        $knockout = $generated['phase'];
        $bracket = $service->bracket((int) $knockout['id']);
        assert_same(4, count($bracket['quarterfinals'] ?? []), 'Quartas incompletas');
        assert_same(2, count($bracket['semifinals'] ?? []), 'Semifinais incompletas');
        assert_same(1, count($bracket['final'] ?? []), 'Final ausente');
        foreach ($bracket['quarterfinals'] as $tie) assert_true((int) $tie['match_id'] > 0, 'Quartas sem partida');

        foreach ($bracket['quarterfinals'] as $tie) self::finishTie($pdo, $repository, $service, $tie, $adminId, 1, 0);
        $bracket = $service->bracket((int) $knockout['id']);
        foreach ($bracket['semifinals'] as $tie) assert_true((int) $tie['match_id'] > 0, 'Semifinal nao foi criada apos classificacao');
        foreach ($bracket['semifinals'] as $tie) self::finishTie($pdo, $repository, $service, $tie, $adminId, 0, 0, 2, 3);
        $bracket = $service->bracket((int) $knockout['id']);
        assert_true((int) $bracket['final'][0]['match_id'] > 0, 'Final nao foi criada');
        $final = self::finishTie($pdo, $repository, $service, $bracket['final'][0], $adminId, 0, 0, 4, 3);
        assert_same('penalties', $final['decided_by'], 'Penaltis nao decidiram a final');
        $result = $service->result((int) $phase['championship_id'], (int) $knockout['id']);
        assert_true((int) ($result['champion_team_id'] ?? 0) > 0 && (int) ($result['runner_up_team_id'] ?? 0) > 0, 'Campeao e vice nao registrados');
        $service->processKnockoutMatch($repository->homologatedMatch((int) $bracket['final'][0]['match_id']), $adminId);
        assert_same(1, (int) $pdo->query('SELECT COUNT(*) FROM competition_results')->fetchColumn(), 'Avanco repetido duplicou resultado');
        $before = [(int) $pdo->query('SELECT COUNT(*) FROM knockout_ties')->fetchColumn(), (int) $pdo->query('SELECT COUNT(*) FROM matches WHERE phase_id = ' . (int) $knockout['id'])->fetchColumn()];
        $service->generateKnockout($phase, $adminId);
        assert_same($before, [(int) $pdo->query('SELECT COUNT(*) FROM knockout_ties')->fetchColumn(), (int) $pdo->query('SELECT COUNT(*) FROM matches WHERE phase_id = ' . (int) $knockout['id'])->fetchColumn()], 'Geracao repetida nao foi idempotente');
    }

    private static function administrativeResult(PDO $pdo, int $matchId, int $userId, int $home, int $away): void
    {
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO match_operations (match_id, status, administrative_home_score, administrative_away_score, administrative_result_reason, administrative_result_by, administrative_result_at, created_by, created_at, updated_at) VALUES (?, 'homologated', ?, ?, 'Resultado administrativo de teste', ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = 'homologated', administrative_home_score = VALUES(administrative_home_score), administrative_away_score = VALUES(administrative_away_score), administrative_result_by = VALUES(administrative_result_by), administrative_result_at = VALUES(administrative_result_at), updated_at = VALUES(updated_at)";
        $pdo->prepare($sql)->execute([$matchId, $home, $away, $userId, $now, $userId, $now, $now]);
        $pdo->prepare("UPDATE matches SET status = 'homologated', updated_at = ? WHERE id = ?")->execute([$now, $matchId]);
    }

    private static function finishTie(PDO $pdo, StandingsRepository $repository, StandingsService $service, array $tie, int $userId, int $home, int $away, int $homePenalties = 0, int $awayPenalties = 0): array
    {
        $matchId = (int) $tie['match_id'];
        self::administrativeResult($pdo, $matchId, $userId, $home, $away);
        if ($homePenalties || $awayPenalties) {
            $homeTeam = (int) $tie['home_team_id'];
            $awayTeam = (int) $tie['away_team_id'];
            $now = date('Y-m-d H:i:s');
            $insert = $pdo->prepare("INSERT INTO match_operation_events (match_id, team_id, event_type, period, valid, created_by, created_at, updated_at) VALUES (?, ?, 'penalty_scored', 'penalties', 1, ?, ?, ?)");
            for ($i = 0; $i < $homePenalties; $i++) $insert->execute([$matchId, $homeTeam, $userId, $now, $now]);
            for ($i = 0; $i < $awayPenalties; $i++) $insert->execute([$matchId, $awayTeam, $userId, $now, $now]);
        }
        $result = $service->processKnockoutMatch($repository->homologatedMatch($matchId), $userId);
        assert_true($result['ok'], 'Partida eliminatoria nao avancou');
        return $result;
    }
}
