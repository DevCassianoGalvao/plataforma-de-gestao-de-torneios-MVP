<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class LineupRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $matchId, int $teamId): ?array
    {
        $statement = $this->pdo->prepare('SELECT ml.*, m.championship_id, m.phase_id, m.group_id, m.home_team_id, m.away_team_id, m.match_date, m.match_time, m.status AS match_status, ht.name AS home_team_name, at.name AS away_team_name, t.name AS team_name, t.short_name AS team_short_name, f.name AS formation_name FROM match_lineups ml INNER JOIN matches m ON m.id = ml.match_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id INNER JOIN teams t ON t.id = ml.team_id INNER JOIN tactical_formations f ON f.id = ml.tactical_formation_id WHERE ml.match_id = ? AND ml.team_id = ? LIMIT 1');
        $statement->execute([$matchId, $teamId]);
        $lineup = $statement->fetch();
        if (!$lineup) return null;
        $lineup['players'] = $this->players((int) $lineup['id']);
        $lineup['staff'] = $this->lineupStaff((int) $lineup['id']);
        return $lineup;
    }

    public function listForMatch(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT ml.id, ml.match_id, ml.team_id, ml.status, ml.version, ml.tactical_formation_id, ml.confirmed_at, t.name AS team_name, t.short_name AS team_short_name, f.name AS formation_name, (SELECT COUNT(*) FROM match_lineup_players p WHERE p.lineup_id = ml.id AND p.role = \'starter\') AS starters_count, (SELECT COUNT(*) FROM match_lineup_players p WHERE p.lineup_id = ml.id AND p.role = \'reserve\') AS reserves_count FROM match_lineups ml INNER JOIN teams t ON t.id = ml.team_id INNER JOIN tactical_formations f ON f.id = ml.tactical_formation_id WHERE ml.match_id = ? ORDER BY t.name');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function formationSlots(int $formationId): array
    {
        $statement = $this->pdo->prepare('SELECT slot_key, position_code, label, position_group, horizontal_position, vertical_position, display_order FROM tactical_formation_slots WHERE tactical_formation_id = ? ORDER BY display_order, id');
        $statement->execute([$formationId]);
        return $statement->fetchAll();
    }

    public function eligibleAthletes(int $championshipId, int $teamId): array
    {
        $statement = $this->pdo->prepare("SELECT a.id, a.full_name, a.sporting_name, a.photo_path, a.preferred_number, a.status, p.code AS primary_position_code, p.name AS primary_position_name, p.position_group AS primary_position_group, COALESCE(GROUP_CONCAT(DISTINCT sp.code ORDER BY sp.code SEPARATOR ','), '') AS secondary_position_codes, COALESCE(GROUP_CONCAT(DISTINCT sp.position_group ORDER BY sp.position_group SEPARATOR ','), '') AS secondary_position_groups FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN positions p ON p.id = a.primary_position_id LEFT JOIN athlete_secondary_positions asp ON asp.athlete_id = a.id LEFT JOIN positions sp ON sp.id = asp.position_id WHERE ar.championship_id = ? AND ar.team_id = ? AND ar.status = 'approved' AND a.team_id = ? AND a.status = 'active' AND a.deleted_at IS NULL GROUP BY a.id, a.full_name, a.sporting_name, a.photo_path, a.preferred_number, a.status, p.code, p.name, p.position_group ORDER BY a.preferred_number IS NULL, a.preferred_number, a.full_name");
        $statement->execute([$championshipId, $teamId, $teamId]);
        return $statement->fetchAll();
    }

    public function staff(int $teamId): array
    {
        $statement = $this->pdo->prepare("SELECT ts.id, ts.team_id, ts.full_name, ts.display_name, ts.status, sr.name AS role_name, sr.`key` AS role_key FROM team_staff ts INNER JOIN staff_roles sr ON sr.id = ts.staff_role_id WHERE ts.team_id = ? AND ts.status = 'active' AND ts.deleted_at IS NULL ORDER BY sr.display_order, ts.full_name");
        $statement->execute([$teamId]);
        return $statement->fetchAll();
    }

    private function lineupStaff(int $lineupId): array
    {
        $statement = $this->pdo->prepare('SELECT ls.team_staff_id, ls.present, ts.full_name, ts.display_name, sr.name AS role_name, sr.`key` AS role_key FROM match_lineup_staff ls INNER JOIN team_staff ts ON ts.id = ls.team_staff_id INNER JOIN staff_roles sr ON sr.id = ts.staff_role_id WHERE ls.lineup_id = ? ORDER BY sr.display_order, ts.full_name');
        $statement->execute([$lineupId]);
        return $statement->fetchAll();
    }

    public function create(int $matchId, int $teamId, int $formationId, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO match_lineups (match_id, team_id, tactical_formation_id, status, version, created_by, created_at, updated_at) VALUES (?, ?, ?, \'draft\', 1, ?, ?, ?)');
        $statement->execute([$matchId, $teamId, $formationId, $userId, $now, $now]);
        $id = (int) $this->pdo->lastInsertId();
        $this->recordHistory($id, 'created', 1, 'draft', $formationId, null, $userId);
        return $id;
    }

    public function saveContent(int $id, int $formationId, ?int $captainId, ?int $goalkeeperId, array $players, array $staffIds, int $userId, string $status, string $action, ?string $reason = null): int
    {
        $lineup = $this->findById($id);
        if (!$lineup) throw new \RuntimeException('Escalacao nao encontrada.');
        $version = (int) $lineup['version'] + 1;
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('UPDATE match_lineups SET tactical_formation_id = ?, status = ?, version = ?, captain_athlete_id = ?, goalkeeper_athlete_id = ?, confirmed_by = CASE WHEN ? = \'confirmed\' THEN ? ELSE confirmed_by END, confirmed_at = CASE WHEN ? = \'confirmed\' THEN ? ELSE confirmed_at END, updated_at = ? WHERE id = ?');
        $statement->execute([$formationId, $status, $version, $captainId, $goalkeeperId, $status, $userId, $status, $now, $now, $id]);
        $this->pdo->prepare('DELETE FROM match_lineup_players WHERE lineup_id = ?')->execute([$id]);
        $playerStatement = $this->pdo->prepare('INSERT INTO match_lineup_players (lineup_id, athlete_id, role, slot_key, position_code, shirt_number, is_captain, is_goalkeeper, is_out_of_position, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($players as $player) $playerStatement->execute([$id, $player['athlete_id'], $player['role'], $player['slot_key'], $player['position_code'], $player['shirt_number'], $player['is_captain'], $player['is_goalkeeper'], $player['is_out_of_position'], $player['display_order'], $now, $now]);
        $this->pdo->prepare('DELETE FROM match_lineup_staff WHERE lineup_id = ?')->execute([$id]);
        $staffStatement = $this->pdo->prepare('INSERT INTO match_lineup_staff (lineup_id, team_staff_id, present, created_at, updated_at) VALUES (?, ?, 1, ?, ?)');
        foreach (array_unique(array_map('intval', $staffIds)) as $staffId) $staffStatement->execute([$id, $staffId, $now, $now]);
        $this->recordHistory($id, $action, $version, $status, $formationId, $reason, $userId);
        return $version;
    }

    public function reopen(int $id, int $userId, string $reason): void
    {
        $lineup = $this->findById($id);
        if (!$lineup) throw new \RuntimeException('Escalacao nao encontrada.');
        $version = (int) $lineup['version'] + 1;
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('UPDATE match_lineups SET status = \'draft\', version = ?, reopened_by = ?, reopened_at = ?, reopen_reason = ?, updated_at = ? WHERE id = ? AND status = \'confirmed\'')->execute([$version, $userId, $now, $reason, $now, $id]);
        $this->recordHistory($id, 'reopened', $version, 'draft', (int) $lineup['tactical_formation_id'], $reason, $userId);
    }

    public function history(int $lineupId): array
    {
        $statement = $this->pdo->prepare('SELECT h.*, u.name AS user_name, f.name AS formation_name FROM match_lineup_history h INNER JOIN users u ON u.id = h.changed_by LEFT JOIN tactical_formations f ON f.id = h.tactical_formation_id WHERE h.lineup_id = ? ORDER BY h.created_at, h.id');
        $statement->execute([$lineupId]);
        return $statement->fetchAll();
    }

    private function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM match_lineups WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    private function players(int $lineupId): array
    {
        $statement = $this->pdo->prepare('SELECT p.*, a.full_name, a.sporting_name, a.photo_path, a.preferred_number, pos.name AS primary_position_name, pos.code AS primary_position_code FROM match_lineup_players p INNER JOIN athletes a ON a.id = p.athlete_id INNER JOIN positions pos ON pos.code = p.position_code WHERE p.lineup_id = ? ORDER BY p.role, p.display_order, a.full_name');
        $statement->execute([$lineupId]);
        return $statement->fetchAll();
    }

    private function recordHistory(int $id, string $action, int $version, string $status, ?int $formationId, ?string $reason, int $userId): void
    {
        $this->pdo->prepare('INSERT INTO match_lineup_history (lineup_id, action, version, status, tactical_formation_id, reason, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([$id, $action, $version, $status, $formationId, $reason, $userId, date('Y-m-d H:i:s')]);
    }
}
