<?php
declare(strict_types=1);

namespace App\Database;

use App\Repositories\StandingsRepository;
use App\Services\AuditService;
use App\Services\StandingsService;
use PDO;

final class TournamentProgressSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production' && getenv('ALLOW_DEMO_SIMULATION') !== '1') {
            throw new \RuntimeException('Simulacao bloqueada em producao. Defina ALLOW_DEMO_SIMULATION=1 para alterar somente o campeonato ficticio.');
        }

        $championship = $pdo->query("SELECT id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' AND deleted_at IS NULL LIMIT 1")->fetch();
        $adminId = (int) $pdo->query("SELECT id FROM users WHERE email = 'admin@torneios.local' LIMIT 1")->fetchColumn();
        if (!$championship || !$adminId) {
            throw new \RuntimeException('Campeonato ou administrador ficticio nao encontrado para a simulacao.');
        }

        $championshipId = (int) $championship['id'];
        $now = date('Y-m-d H:i:s');
        $today = new \DateTimeImmutable('today');
        $venue = $pdo->prepare('SELECT id FROM venues WHERE championship_id = ? AND status = \'active\' AND deleted_at IS NULL ORDER BY id LIMIT 1');
        $venue->execute([$championshipId]);
        $venueId = (int) $venue->fetchColumn();
        if (!$venueId) {
            throw new \RuntimeException('Local ficticio nao encontrado para a simulacao. Execute o seed base primeiro.');
        }

        $pdo->prepare("UPDATE championships SET status = 'in_progress', visibility = 'public', starts_at = ?, ends_at = ?, updated_at = ? WHERE id = ?")
            ->execute([$today->modify('-28 days')->format('Y-m-d'), $today->modify('+14 days')->format('Y-m-d'), $now, $championshipId]);

        $phase = self::phase($pdo, $championshipId, 'fase-grupos');
        if (!$phase) {
            throw new \RuntimeException('Fase de grupos nao encontrada para a simulacao. Execute o seed base primeiro.');
        }

        self::resetKnockout($pdo, $championshipId, $now);
        self::completeGroupStage($pdo, $phase, $adminId, $today, $now);
        $pdo->prepare("UPDATE competition_rounds SET status = 'finished', updated_at = ? WHERE phase_id = ?")->execute([$now, (int) $phase['id']]);
        $pdo->prepare("UPDATE competition_phases SET status = 'finished', updated_at = ? WHERE id = ?")->execute([$now, (int) $phase['id']]);

        $repository = new StandingsRepository($pdo);
        $standings = new StandingsService($repository, new AuditService($pdo));
        $standings->recalculate($phase, $adminId);
        $knockout = $standings->generateKnockout($phase, $adminId)['phase'];

        self::completeQuarterfinals($pdo, $repository, $standings, $knockout, $adminId, $venueId, $today, $now);
        self::scheduleSemifinals($pdo, (int) $knockout['id'], $venueId, $today, $now);
        $pdo->prepare("UPDATE competition_phases SET status = 'in_progress', published_at = COALESCE(published_at, ?), updated_at = ? WHERE id = ?")
            ->execute([$now, $now, (int) $knockout['id']]);
    }

    private static function completeGroupStage(PDO $pdo, array $phase, int $adminId, \DateTimeImmutable $today, string $now): void
    {
        $matches = $pdo->prepare('SELECT m.*, ht.position AS home_position, at.position AS away_position, g.display_order AS group_order FROM matches m INNER JOIN group_teams ht ON ht.group_id = m.group_id AND ht.team_id = m.home_team_id INNER JOIN group_teams at ON at.group_id = m.group_id AND at.team_id = m.away_team_id INNER JOIN competition_groups g ON g.id = m.group_id WHERE m.phase_id = ? ORDER BY g.display_order, m.round_id, m.match_order, m.id');
        $matches->execute([(int) $phase['id']]);
        $rows = $matches->fetchAll();
        if (count($rows) < 20) {
            throw new \RuntimeException('A simulacao exige os vinte jogos da fase de grupos.');
        }

        self::resetMatchData($pdo, array_map(static fn (array $match): int => (int) $match['id'], $rows));
        foreach ($rows as $index => $match) {
            $homePosition = (int) $match['home_position'];
            $awayPosition = (int) $match['away_position'];
            $homeWins = $homePosition < $awayPosition;
            $difference = abs($homePosition - $awayPosition);
            $winnerGoals = 2 + (($index + $difference) % 2);
            $loserGoals = $difference === 1 ? 1 : 0;
            $homeGoals = $homeWins ? $winnerGoals : $loserGoals;
            $awayGoals = $homeWins ? $loserGoals : $winnerGoals;
            $matchDate = $today->modify('-' . (20 - $index) . ' days')->format('Y-m-d');
            self::completeMatch($pdo, $match, $homeGoals, $awayGoals, null, $adminId, $matchDate, $index % 2 === 0 ? '18:00:00' : '20:00:00', $now);
        }
    }

    private static function completeQuarterfinals(PDO $pdo, StandingsRepository $repository, StandingsService $standings, array $phase, int $adminId, int $venueId, \DateTimeImmutable $today, string $now): void
    {
        self::syncKnockoutMatchTeams($pdo, (int) $phase['id'], 'quarterfinals', $now);
        $ties = $pdo->prepare("SELECT kt.id AS tie_id, kt.tie_number, kt.match_id, m.* FROM knockout_ties kt INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id INNER JOIN matches m ON m.id = kt.match_id WHERE kr.phase_id = ? AND kr.stage = 'quarterfinals' ORDER BY kt.tie_number");
        $ties->execute([(int) $phase['id']]);
        $rows = $ties->fetchAll();
        if (count($rows) !== 4) {
            throw new \RuntimeException('As quatro quartas de final nao foram geradas.');
        }

        self::resetMatchData($pdo, array_map(static fn (array $match): int => (int) $match['match_id'], $rows));
        $results = [
            1 => [3, 1, null],
            2 => [1, 1, [4, 3]],
            3 => [0, 2, null],
            4 => [2, 0, null],
        ];
        foreach ($rows as $index => $match) {
            [$homeGoals, $awayGoals, $penalties] = $results[(int) $match['tie_number']];
            self::completeMatch($pdo, $match, $homeGoals, $awayGoals, $penalties, $adminId, $today->modify('-' . (7 - $index) . ' days')->format('Y-m-d'), $index % 2 === 0 ? '18:30:00' : '20:30:00', $now, $venueId);
            $homologated = $repository->homologatedMatch((int) $match['match_id']);
            if (!$homologated || !$standings->processKnockoutMatch($homologated, $adminId)['ok']) {
                throw new \RuntimeException('Nao foi possivel avancar uma quarta de final na simulacao.');
            }
        }
    }

    private static function scheduleSemifinals(PDO $pdo, int $phaseId, int $venueId, \DateTimeImmutable $today, string $now): void
    {
        self::syncKnockoutMatchTeams($pdo, $phaseId, 'semifinals', $now);
        $semifinals = $pdo->prepare("SELECT m.id, kt.tie_number FROM knockout_ties kt INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id INNER JOIN matches m ON m.id = kt.match_id WHERE kr.phase_id = ? AND kr.stage = 'semifinals' ORDER BY kt.tie_number");
        $semifinals->execute([$phaseId]);
        $rows = $semifinals->fetchAll();
        if (count($rows) !== 2) {
            throw new \RuntimeException('As semifinais nao foram definidas pela progressao das quartas.');
        }
        $update = $pdo->prepare("UPDATE matches SET match_date = ?, match_time = ?, venue_id = ?, status = 'scheduled', observation = 'Semifinal da simulacao de andamento.', updated_at = ? WHERE id = ?");
        foreach ($rows as $index => $match) {
            $update->execute([$today->modify('+' . (3 + $index) . ' days')->format('Y-m-d'), $index === 0 ? '18:30:00' : '20:30:00', $venueId, $now, (int) $match['id']]);
        }
        $pdo->prepare("UPDATE competition_rounds r INNER JOIN matches m ON m.round_id = r.id SET r.status = 'published', r.published_at = COALESCE(r.published_at, ?), r.updated_at = ? WHERE m.id IN (?, ?)")
            ->execute([$now, $now, (int) $rows[0]['id'], (int) $rows[1]['id']]);
        $pdo->prepare("UPDATE knockout_rounds SET status = 'finished', updated_at = ? WHERE phase_id = ? AND stage = 'quarterfinals'")->execute([$now, $phaseId]);
        $pdo->prepare("UPDATE knockout_rounds SET status = 'in_progress', updated_at = ? WHERE phase_id = ? AND stage = 'semifinals'")->execute([$now, $phaseId]);
    }

    private static function completeMatch(PDO $pdo, array $match, int $homeGoals, int $awayGoals, ?array $penalties, int $adminId, string $matchDate, string $matchTime, string $now, ?int $venueId = null): void
    {
        $matchId = (int) ($match['match_id'] ?? $match['id']);
        $homeTeamId = (int) $match['home_team_id'];
        $awayTeamId = (int) $match['away_team_id'];
        $operation = $pdo->prepare("INSERT INTO match_operations (match_id, status, first_half_started_at, first_half_ended_at, second_half_started_at, second_half_ended_at, finalized_by, finalized_at, homologated_by, homologated_at, created_by, created_at, updated_at) VALUES (?, 'homologated', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = 'homologated', first_half_started_at = VALUES(first_half_started_at), first_half_ended_at = VALUES(first_half_ended_at), second_half_started_at = VALUES(second_half_started_at), second_half_ended_at = VALUES(second_half_ended_at), finalized_by = VALUES(finalized_by), finalized_at = VALUES(finalized_at), homologated_by = VALUES(homologated_by), homologated_at = VALUES(homologated_at), updated_at = VALUES(updated_at)");
        $firstStart = $matchDate . ' ' . $matchTime;
        $operation->execute([$matchId, $firstStart, $matchDate . ' 19:10:00', $matchDate . ' 19:20:00', $matchDate . ' 20:00:00', $adminId, $now, $adminId, $now, $adminId, $now, $now]);
        $pdo->prepare("UPDATE matches SET match_date = ?, match_time = ?, venue_id = COALESCE(?, venue_id), status = 'homologated', observation = 'Resultado homologado pela simulacao de andamento.', updated_at = ? WHERE id = ?")
            ->execute([$matchDate, $matchTime, $venueId, $now, $matchId]);

        self::recordGoals($pdo, $matchId, $homeTeamId, $homeGoals, $adminId, $now, 7);
        self::recordGoals($pdo, $matchId, $awayTeamId, $awayGoals, $adminId, $now, 13);
        if ($penalties !== null) {
            self::recordPenalties($pdo, $matchId, $homeTeamId, (int) $penalties[0], $adminId, $now);
            self::recordPenalties($pdo, $matchId, $awayTeamId, (int) $penalties[1], $adminId, $now);
        }
    }

    private static function recordGoals(PDO $pdo, int $matchId, int $teamId, int $goals, int $adminId, string $now, int $minuteOffset): void
    {
        if ($goals < 1) {
            return;
        }
        $players = self::players($pdo, $teamId);
        $insert = $pdo->prepare("INSERT INTO match_operation_events (match_id, team_id, athlete_id, related_athlete_id, event_type, period, minute, notes, valid, created_by, created_at, updated_at) VALUES (?, ?, ?, NULL, 'goal', 'regular', ?, 'Simulacao de andamento - gol.', 1, ?, ?, ?)");
        $assist = $pdo->prepare("INSERT INTO match_operation_events (match_id, team_id, athlete_id, related_athlete_id, event_type, period, minute, notes, valid, created_by, created_at, updated_at) VALUES (?, ?, ?, NULL, 'assist', 'regular', ?, 'Simulacao de andamento - assistencia.', 1, ?, ?, ?)");
        for ($index = 0; $index < $goals; $index++) {
            $scorer = $players[$index % count($players)];
            $minute = $minuteOffset + ($index * 9);
            $insert->execute([$matchId, $teamId, $scorer, $minute, $adminId, $now, $now]);
            if ($index === 0 && count($players) > 1) {
                $assist->execute([$matchId, $teamId, $players[1], $minute, $adminId, $now, $now]);
            }
        }
    }

    private static function recordPenalties(PDO $pdo, int $matchId, int $teamId, int $goals, int $adminId, string $now): void
    {
        $players = self::players($pdo, $teamId);
        $insert = $pdo->prepare("INSERT INTO match_operation_events (match_id, team_id, athlete_id, related_athlete_id, event_type, period, minute, notes, valid, created_by, created_at, updated_at) VALUES (?, ?, ?, NULL, 'penalty_scored', 'penalties', NULL, 'Simulacao de andamento - disputa de penaltis.', 1, ?, ?, ?)");
        for ($index = 0; $index < $goals; $index++) {
            $insert->execute([$matchId, $teamId, $players[$index % count($players)], $adminId, $now, $now]);
        }
    }

    private static function players(PDO $pdo, int $teamId): array
    {
        $statement = $pdo->prepare("SELECT a.id FROM athletes a INNER JOIN athlete_registrations ar ON ar.athlete_id = a.id WHERE a.team_id = ? AND a.status = 'active' AND a.deleted_at IS NULL AND ar.status = 'approved' ORDER BY a.preferred_number, a.id LIMIT 5");
        $statement->execute([$teamId]);
        $players = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
        if ($players === []) {
            throw new \RuntimeException('Atletas aprovados nao encontrados para a simulacao.');
        }
        return $players;
    }

    private static function resetKnockout(PDO $pdo, int $championshipId, string $now): void
    {
        $statement = $pdo->prepare("SELECT id FROM competition_phases WHERE championship_id = ? AND phase_type = 'knockout' LIMIT 1");
        $statement->execute([$championshipId]);
        $phaseId = (int) $statement->fetchColumn();
        if (!$phaseId) {
            return;
        }

        $matches = $pdo->prepare('SELECT id FROM matches WHERE phase_id = ?');
        $matches->execute([$phaseId]);
        self::resetMatchData($pdo, array_map('intval', $matches->fetchAll(PDO::FETCH_COLUMN)));
        $pdo->prepare("UPDATE matches SET match_date = NULL, match_time = NULL, venue_id = NULL, status = 'scheduled', observation = NULL, updated_at = ? WHERE phase_id = ?")
            ->execute([$now, $phaseId]);
        $pdo->prepare("UPDATE knockout_ties SET winner_team_id = NULL, loser_team_id = NULL, status = CASE WHEN home_team_id IS NOT NULL AND away_team_id IS NOT NULL THEN 'ready' ELSE 'pending' END, decided_by = NULL, decided_at = NULL, updated_at = ? WHERE knockout_round_id IN (SELECT id FROM knockout_rounds WHERE phase_id = ?)")
            ->execute([$now, $phaseId]);
        $pdo->prepare("UPDATE knockout_rounds SET status = 'draft', updated_at = ? WHERE phase_id = ?")->execute([$now, $phaseId]);
        $pdo->prepare('DELETE FROM competition_results WHERE championship_id = ? AND phase_id = ?')->execute([$championshipId, $phaseId]);
    }

    private static function syncKnockoutMatchTeams(PDO $pdo, int $phaseId, string $stage, string $now): void
    {
        $statement = $pdo->prepare("UPDATE matches m INNER JOIN knockout_ties kt ON kt.match_id = m.id INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id SET m.home_team_id = kt.home_team_id, m.away_team_id = kt.away_team_id, m.updated_at = ? WHERE kr.phase_id = ? AND kr.stage = ? AND kt.home_team_id IS NOT NULL AND kt.away_team_id IS NOT NULL");
        $statement->execute([$now, $phaseId, $stage]);
    }

    private static function resetMatchData(PDO $pdo, array $matchIds): void
    {
        if ($matchIds === []) {
            return;
        }
        $marks = implode(',', array_fill(0, count($matchIds), '?'));
        $statement = $pdo->prepare("DELETE h FROM match_operation_history h INNER JOIN match_operations mo ON mo.id = h.operation_id WHERE mo.match_id IN ({$marks})");
        $statement->execute($matchIds);
        $statement = $pdo->prepare("DELETE FROM match_operation_events WHERE match_id IN ({$marks})");
        $statement->execute($matchIds);
        $statement = $pdo->prepare("UPDATE match_operations SET status = 'open', first_half_started_at = NULL, first_half_ended_at = NULL, second_half_started_at = NULL, second_half_ended_at = NULL, extra_time_started_at = NULL, extra_time_ended_at = NULL, administrative_home_score = NULL, administrative_away_score = NULL, administrative_result_reason = NULL, administrative_result_by = NULL, administrative_result_at = NULL, finalized_by = NULL, finalized_at = NULL, homologated_by = NULL, homologated_at = NULL, updated_at = ? WHERE match_id IN ({$marks})");
        $statement->execute(array_merge([date('Y-m-d H:i:s')], $matchIds));
    }

    private static function phase(PDO $pdo, int $championshipId, string $slug): ?array
    {
        $statement = $pdo->prepare('SELECT * FROM competition_phases WHERE championship_id = ? AND slug = ? LIMIT 1');
        $statement->execute([$championshipId, $slug]);
        return $statement->fetch() ?: null;
    }
}
