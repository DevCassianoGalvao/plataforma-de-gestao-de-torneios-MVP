<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StandingsRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function phase(int $phaseId): ?array
    {
        $statement = $this->pdo->prepare('SELECT p.*, c.name AS championship_name FROM competition_phases p INNER JOIN championships c ON c.id = p.championship_id WHERE p.id = ? LIMIT 1');
        $statement->execute([$phaseId]);
        return $statement->fetch() ?: null;
    }

    public function groups(int $phaseId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM competition_groups WHERE phase_id = ? ORDER BY display_order, id');
        $statement->execute([$phaseId]);
        return $statement->fetchAll();
    }

    public function groupTeams(int $groupId): array
    {
        $statement = $this->pdo->prepare("SELECT gt.team_id, t.name, t.short_name FROM group_teams gt INNER JOIN teams t ON t.id = gt.team_id WHERE gt.group_id = ? AND gt.status = 'active' ORDER BY t.name");
        $statement->execute([$groupId]);
        return $statement->fetchAll();
    }

    public function regulation(int $championshipId): array
    {
        $statement = $this->pdo->prepare("SELECT r.id, ps.points_win, ps.points_draw, ps.points_loss, ps.wo_winner_goals, ps.wo_loser_goals, fs.qualified_per_group FROM regulations r INNER JOIN regulation_points_settings ps ON ps.regulation_id = r.id INNER JOIN regulation_format_settings fs ON fs.regulation_id = r.id WHERE r.championship_id = ? AND r.status = 'published' ORDER BY r.version_number DESC LIMIT 1");
        $statement->execute([$championshipId]);
        $regulation = $statement->fetch() ?: ['id' => 0, 'points_win' => 3, 'points_draw' => 1, 'points_loss' => 0, 'wo_winner_goals' => 3, 'wo_loser_goals' => 0, 'qualified_per_group' => 4];
        $tie = $this->pdo->prepare('SELECT criterion, priority FROM regulation_tiebreakers WHERE regulation_id = ? AND enabled = 1 ORDER BY priority');
        $tie->execute([(int) $regulation['id']]);
        $regulation['tiebreakers'] = $tie->fetchAll();
        return $regulation;
    }

    public function homologatedMatches(int $groupId): array
    {
        $sql = "SELECT m.id, m.championship_id, m.phase_id, m.group_id, m.home_team_id, m.away_team_id, m.status, m.match_date, m.match_time, mo.administrative_home_score, mo.administrative_away_score, COALESCE((SELECT SUM(CASE WHEN e.team_id = m.home_team_id THEN 1 ELSE 0 END) FROM match_operation_events e WHERE e.match_id = m.id AND e.valid = 1 AND e.event_type IN ('goal', 'own_goal') AND e.period <> 'penalties'), 0) AS event_home_score, COALESCE((SELECT SUM(CASE WHEN e.team_id = m.away_team_id THEN 1 ELSE 0 END) FROM match_operation_events e WHERE e.match_id = m.id AND e.valid = 1 AND e.event_type IN ('goal', 'own_goal') AND e.period <> 'penalties'), 0) AS event_away_score, COALESCE((SELECT SUM(CASE WHEN e.team_id = m.home_team_id THEN 1 ELSE 0 END) FROM match_operation_events e WHERE e.match_id = m.id AND e.valid = 1 AND e.event_type = 'penalty_scored' AND e.period = 'penalties'), 0) AS home_penalties, COALESCE((SELECT SUM(CASE WHEN e.team_id = m.away_team_id THEN 1 ELSE 0 END) FROM match_operation_events e WHERE e.match_id = m.id AND e.valid = 1 AND e.event_type = 'penalty_scored' AND e.period = 'penalties'), 0) AS away_penalties FROM matches m LEFT JOIN match_operations mo ON mo.match_id = m.id WHERE m.group_id = ? AND m.status = 'homologated' ORDER BY m.match_date, m.match_time, m.id";
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$groupId]);
        return $statement->fetchAll();
    }

    public function phaseMatchesPending(int $phaseId): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM matches WHERE phase_id = ? AND status NOT IN ('homologated', 'cancelled')");
        $statement->execute([$phaseId]);
        return (int) $statement->fetchColumn();
    }

    public function knockoutPairings(int $regulationId, string $stage): array
    {
        $statement = $this->pdo->prepare('SELECT tie_number, home_source, away_source FROM regulation_knockout_pairings WHERE regulation_id = ? AND stage = ? ORDER BY tie_number');
        $statement->execute([$regulationId, $stage]);
        return $statement->fetchAll();
    }

    public function homologatedMatch(int $matchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT group_id FROM matches WHERE id = ? AND status = \'homologated\' LIMIT 1');
        $statement->execute([$matchId]);
        $groupId = (int) $statement->fetchColumn();
        if (!$groupId) return null;
        foreach ($this->homologatedMatches($groupId) as $match) if ((int) $match['id'] === $matchId) return $match;
        return null;
    }

    public function disciplineCards(int $championshipId, int $teamId): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM discipline_ledger WHERE championship_id = ? AND team_id = ? AND status = 'considered'");
        $statement->execute([$championshipId, $teamId]);
        return (int) $statement->fetchColumn();
    }

    public function administrativeScore(int $groupId, int $teamId): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM administrative_decisions d INNER JOIN matches m ON m.id = d.match_id WHERE d.group_id = ? AND d.team_id = ? AND d.status = 'recorded'");
        $statement->execute([$groupId, $teamId]);
        return (int) $statement->fetchColumn();
    }

    public function directMatches(int $groupId, array $teamIds): array
    {
        if (count($teamIds) < 2) return [];
        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $sql = "SELECT m.*, mo.administrative_home_score, mo.administrative_away_score, COALESCE((SELECT SUM(CASE WHEN e.team_id = m.home_team_id THEN 1 ELSE 0 END) FROM match_operation_events e WHERE e.match_id = m.id AND e.valid = 1 AND e.event_type IN ('goal', 'own_goal') AND e.period <> 'penalties'), 0) AS event_home_score, COALESCE((SELECT SUM(CASE WHEN e.team_id = m.away_team_id THEN 1 ELSE 0 END) FROM match_operation_events e WHERE e.match_id = m.id AND e.valid = 1 AND e.event_type IN ('goal', 'own_goal') AND e.period <> 'penalties'), 0) AS event_away_score FROM matches m LEFT JOIN match_operations mo ON mo.match_id = m.id WHERE m.group_id = ? AND m.status = 'homologated' AND m.home_team_id IN ({$placeholders}) AND m.away_team_id IN ({$placeholders}) ORDER BY m.id";
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_merge([$groupId], $teamIds, $teamIds));
        return $statement->fetchAll();
    }

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }

    public function replaceGroupStandings(int $groupId, array $rows, int $userId, string $hash, array $phase): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('DELETE FROM competition_standings WHERE group_id = ?')->execute([$groupId]);
        $statement = $this->pdo->prepare('INSERT INTO competition_standings (championship_id, phase_id, group_id, team_id, matches_played, wins, draws, losses, goals_for, goals_against, goal_difference, points, win_percentage, position, situation, separated_by, calculation_hash, calculated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($rows as $row) $statement->execute([$phase['championship_id'], $phase['id'], $groupId, $row['team_id'], $row['matches_played'], $row['wins'], $row['draws'], $row['losses'], $row['goals_for'], $row['goals_against'], $row['goal_difference'], $row['points'], $row['win_percentage'], $row['position'], $row['situation'], $row['separated_by'], $hash, $now]);
        $run = $this->pdo->prepare('INSERT INTO standings_calculation_runs (championship_id, phase_id, group_id, source_hash, calculated_by, calculated_at) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE calculated_by = VALUES(calculated_by), calculated_at = VALUES(calculated_at)');
        $run->execute([$phase['championship_id'], $phase['id'], $groupId, $hash, $userId, $now]);
    }

    public function standings(int $groupId): array
    {
        $statement = $this->pdo->prepare('SELECT s.*, t.name AS team_name, t.short_name FROM competition_standings s INNER JOIN teams t ON t.id = s.team_id WHERE s.group_id = ? ORDER BY s.position');
        $statement->execute([$groupId]);
        return $statement->fetchAll();
    }

    public function allStandings(int $phaseId): array
    {
        $statement = $this->pdo->prepare('SELECT s.*, g.name AS group_name, t.name AS team_name, t.short_name FROM competition_standings s INNER JOIN competition_groups g ON g.id = s.group_id INNER JOIN teams t ON t.id = s.team_id WHERE s.phase_id = ? ORDER BY g.display_order, s.position');
        $statement->execute([$phaseId]);
        return $statement->fetchAll();
    }

    public function knockoutPhase(int $championshipId): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM competition_phases WHERE championship_id = ? AND phase_type = 'knockout' ORDER BY sequence_number DESC, id DESC LIMIT 1");
        $statement->execute([$championshipId]);
        return $statement->fetch() ?: null;
    }

    public function ensureKnockoutPhase(array $sourcePhase, int $userId): array
    {
        $existing = $this->knockoutPhase((int) $sourcePhase['championship_id']);
        if ($existing) return $existing;
        $now = date('Y-m-d H:i:s');
        $sequence = (int) $sourcePhase['sequence_number'] + 1;
        $this->pdo->prepare("INSERT INTO competition_phases (championship_id, name, slug, phase_type, sequence_number, group_count, teams_per_group, qualified_per_group, status, created_by, created_at, updated_at) VALUES (?, 'Mata-mata', 'mata-mata', 'knockout', ?, 1, 8, 0, 'published', ?, ?, ?)")->execute([$sourcePhase['championship_id'], $sequence, $userId, $now, $now]);
        $phaseId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO competition_groups (phase_id, name, code, display_order, teams_limit, qualified_limit, status, published_at, created_at, updated_at) VALUES (?, 'Chave principal', 'KO', 1, 8, 0, 'published', ?, ?, ?)")->execute([$phaseId, $now, $now, $now]);
        return $this->knockoutPhase((int) $sourcePhase['championship_id']) ?: throw new \RuntimeException('Fase de mata-mata nao criada.');
    }

    public function ensureKnockoutRound(array $phase, string $stage, int $sequence, int $userId): int
    {
        $statement = $this->pdo->prepare('SELECT id FROM knockout_rounds WHERE phase_id = ? AND stage = ? LIMIT 1');
        $statement->execute([$phase['id'], $stage]);
        $id = (int) $statement->fetchColumn();
        if ($id) return $id;
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('INSERT INTO knockout_rounds (championship_id, phase_id, stage, sequence_number, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, \'draft\', ?, ?, ?)')->execute([$phase['championship_id'], $phase['id'], $stage, $sequence, $userId, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function upsertTie(int $roundId, int $number, string $homeSource, string $awaySource, ?int $homeTeam, ?int $awayTeam): int
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('INSERT INTO knockout_ties (knockout_round_id, tie_number, home_source, away_source, home_team_id, away_team_id, status, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE home_source = VALUES(home_source), away_source = VALUES(away_source), home_team_id = VALUES(home_team_id), away_team_id = VALUES(away_team_id), status = CASE WHEN status = \'finished\' THEN status ELSE VALUES(status) END, updated_at = VALUES(updated_at)')->execute([$roundId, $number, $homeSource, $awaySource, $homeTeam, $awayTeam, $homeTeam && $awayTeam ? 'ready' : 'pending', $now]);
        $statement = $this->pdo->prepare('SELECT id FROM knockout_ties WHERE knockout_round_id = ? AND tie_number = ? LIMIT 1');
        $statement->execute([$roundId, $number]);
        return (int) $statement->fetchColumn();
    }

    public function ties(int $roundId): array
    {
        $statement = $this->pdo->prepare('SELECT kt.*, ht.name AS home_team_name, at.name AS away_team_name, w.name AS winner_team_name, m.status AS match_status FROM knockout_ties kt LEFT JOIN teams ht ON ht.id = kt.home_team_id LEFT JOIN teams at ON at.id = kt.away_team_id LEFT JOIN teams w ON w.id = kt.winner_team_id LEFT JOIN matches m ON m.id = kt.match_id WHERE kt.knockout_round_id = ? ORDER BY kt.tie_number');
        $statement->execute([$roundId]);
        return $statement->fetchAll();
    }

    public function tie(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT kt.*, kr.stage, kr.phase_id, kr.championship_id FROM knockout_ties kt INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id WHERE kt.id = ? LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function knockoutRounds(int $phaseId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM knockout_rounds WHERE phase_id = ? ORDER BY sequence_number');
        $statement->execute([$phaseId]);
        return $statement->fetchAll();
    }

    public function knockoutMatch(int $matchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT kt.*, kr.stage, kr.phase_id, kr.championship_id FROM knockout_ties kt INNER JOIN knockout_rounds kr ON kr.id = kt.knockout_round_id WHERE kt.match_id = ? LIMIT 1');
        $statement->execute([$matchId]);
        return $statement->fetch() ?: null;
    }

    public function createKnockoutMatch(array $phase, int $groupId, int $home, int $away, int $roundNumber, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $round = $this->pdo->prepare('INSERT INTO competition_rounds (phase_id, group_id, round_number, period_start, period_end, status, published_at, created_at, updated_at) VALUES (?, ?, ?, NULL, NULL, \'draft\', NULL, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $round->execute([$phase['id'], $groupId, $roundNumber, $now, $now]);
        $roundId = (int) ($this->pdo->lastInsertId() ?: $this->roundId((int) $phase['id'], $groupId, $roundNumber));
        $fixture = hash('sha256', implode(':', ['knockout', $phase['id'], $roundNumber, $home, $away]));
        $match = $this->pdo->prepare("INSERT INTO matches (championship_id, phase_id, group_id, round_id, home_team_id, away_team_id, fixture_key, leg_number, match_order, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, 'scheduled', ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
        $match->execute([$phase['championship_id'], $phase['id'], $groupId, $roundId, $home, $away, $fixture, $roundNumber, $userId, $now, $now]);
        return (int) ($this->pdo->lastInsertId() ?: $this->matchIdByFixture($fixture));
    }

    public function attachTieMatch(int $tieId, int $matchId): void
    {
        $this->pdo->prepare("UPDATE knockout_ties SET match_id = ?, status = 'scheduled', updated_at = ? WHERE id = ? AND match_id IS NULL")->execute([$matchId, date('Y-m-d H:i:s'), $tieId]);
    }

    public function updateTieDecision(int $tieId, int $winner, int $loser, string $decidedBy): void
    {
        $this->pdo->prepare("UPDATE knockout_ties SET winner_team_id = ?, loser_team_id = ?, status = 'finished', decided_by = ?, decided_at = ?, updated_at = ? WHERE id = ?")->execute([$winner, $loser, $decidedBy, date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $tieId]);
    }

    public function result(int $championshipId, int $phaseId): ?array
    {
        $statement = $this->pdo->prepare('SELECT r.*, c.name AS champion_name, ru.name AS runner_up_name FROM competition_results r LEFT JOIN teams c ON c.id = r.champion_team_id LEFT JOIN teams ru ON ru.id = r.runner_up_team_id WHERE r.championship_id = ? AND r.phase_id = ? LIMIT 1');
        $statement->execute([$championshipId, $phaseId]);
        return $statement->fetch() ?: null;
    }

    public function saveResult(int $championshipId, int $phaseId, int $champion, int $runnerUp, int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('INSERT INTO competition_results (championship_id, phase_id, champion_team_id, runner_up_team_id, decided_by, decided_at) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE champion_team_id = VALUES(champion_team_id), runner_up_team_id = VALUES(runner_up_team_id), decided_by = VALUES(decided_by), decided_at = VALUES(decided_at)')->execute([$championshipId, $phaseId, $champion, $runnerUp, $userId, $now]);
    }

    private function roundId(int $phaseId, int $groupId, int $number): int
    {
        $statement = $this->pdo->prepare('SELECT id FROM competition_rounds WHERE phase_id = ? AND group_id = ? AND round_number = ? LIMIT 1');
        $statement->execute([$phaseId, $groupId, $number]);
        return (int) $statement->fetchColumn();
    }

    private function matchIdByFixture(string $fixture): int
    {
        $statement = $this->pdo->prepare('SELECT id FROM matches WHERE fixture_key = ? LIMIT 1');
        $statement->execute([$fixture]);
        return (int) $statement->fetchColumn();
    }
}
