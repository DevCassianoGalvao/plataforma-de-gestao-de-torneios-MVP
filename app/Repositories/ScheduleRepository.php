<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ScheduleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listPhases(int $championshipId): array
    {
        $statement = $this->pdo->prepare('SELECT p.*, (SELECT COUNT(*) FROM competition_groups g WHERE g.phase_id = p.id) AS groups_count, (SELECT COUNT(*) FROM group_teams gt WHERE gt.phase_id = p.id AND gt.status = \'active\') AS teams_count FROM competition_phases p WHERE p.championship_id = ? ORDER BY p.sequence_number, p.id');
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function phase(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT p.*, c.name AS championship_name, c.slug AS championship_slug FROM competition_phases p INNER JOIN championships c ON c.id = p.championship_id WHERE p.id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function createPhase(array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO competition_phases (championship_id, name, slug, phase_type, sequence_number, group_count, teams_per_group, qualified_per_group, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'draft\', ?, ?, ?)');
        $statement->execute([$data['championship_id'], $data['name'], $data['slug'], $data['phase_type'] ?: 'groups', (int) $data['sequence_number'], (int) $data['group_count'], (int) $data['teams_per_group'], (int) $data['qualified_per_group'], $userId, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updatePhase(int $id, array $data): void
    {
        $this->pdo->prepare('UPDATE competition_phases SET name = ?, slug = ?, group_count = ?, teams_per_group = ?, qualified_per_group = ?, updated_at = ? WHERE id = ?')->execute([$data['name'], $data['slug'], (int) $data['group_count'], (int) $data['teams_per_group'], (int) $data['qualified_per_group'], date('Y-m-d H:i:s'), $id]);
    }

    public function updatePhaseStatus(int $id, string $status): void
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('UPDATE competition_phases SET status = ?, published_at = CASE WHEN ? = \'published\' THEN COALESCE(published_at, ?) ELSE published_at END, started_at = CASE WHEN ? = \'in_progress\' THEN COALESCE(started_at, ?) ELSE started_at END, updated_at = ? WHERE id = ?');
        $statement->execute([$status, $status, $now, $status, $now, $now, $id]);
    }

    public function listGroups(int $phaseId): array
    {
        $statement = $this->pdo->prepare('SELECT g.*, (SELECT COUNT(*) FROM group_teams gt WHERE gt.group_id = g.id AND gt.status = \'active\') AS active_teams_count FROM competition_groups g WHERE g.phase_id = ? ORDER BY g.display_order, g.id');
        $statement->execute([$phaseId]);
        return $statement->fetchAll();
    }

    public function group(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT g.*, p.championship_id, p.name AS phase_name, p.status AS phase_status, p.teams_per_group, p.qualified_per_group FROM competition_groups g INNER JOIN competition_phases p ON p.id = g.phase_id WHERE g.id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function createGroup(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO competition_groups (phase_id, name, code, display_order, teams_limit, qualified_limit, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, \'draft\', ?, ?)');
        $statement->execute([$data['phase_id'], $data['name'], $data['code'], (int) $data['display_order'], (int) $data['teams_limit'], (int) $data['qualified_limit'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateGroup(int $id, array $data): void
    {
        $this->pdo->prepare('UPDATE competition_groups SET name = ?, code = ?, display_order = ?, teams_limit = ?, qualified_limit = ?, updated_at = ? WHERE id = ?')->execute([$data['name'], $data['code'], (int) $data['display_order'], (int) $data['teams_limit'], (int) $data['qualified_limit'], date('Y-m-d H:i:s'), $id]);
    }

    public function updateGroupStatus(int $id, string $status): void
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('UPDATE competition_groups SET status = ?, published_at = CASE WHEN ? = \'published\' THEN COALESCE(published_at, ?) ELSE published_at END, started_at = CASE WHEN ? = \'in_progress\' THEN COALESCE(started_at, ?) ELSE started_at END, updated_at = ? WHERE id = ?');
        $statement->execute([$status, $status, $now, $status, $now, $now, $id]);
    }

    public function listGroupTeams(int $groupId): array
    {
        $statement = $this->pdo->prepare('SELECT gt.*, t.name AS team_name, t.short_name, t.slug AS team_slug, t.status AS team_status FROM group_teams gt INNER JOIN teams t ON t.id = gt.team_id WHERE gt.group_id = ? ORDER BY gt.position IS NULL, gt.position, t.name');
        $statement->execute([$groupId]);
        return $statement->fetchAll();
    }

    public function listAvailableTeams(int $championshipId, int $phaseId, int $groupId = 0): array
    {
        $sql = 'SELECT t.*, c.name AS championship_name FROM teams t INNER JOIN championships c ON c.id = t.championship_id WHERE t.championship_id = ? AND t.deleted_at IS NULL AND t.status = \'active\' AND NOT EXISTS (SELECT 1 FROM group_teams gt WHERE gt.phase_id = ? AND gt.team_id = t.id AND gt.status = \'active\')';
        $params = [$championshipId, $phaseId];
        if ($groupId > 0) {
            $sql .= ' OR (t.championship_id = ? AND t.deleted_at IS NULL AND t.status = \'active\' AND EXISTS (SELECT 1 FROM group_teams gt2 WHERE gt2.group_id = ? AND gt2.team_id = t.id AND gt2.status = \'withdrawn\'))';
            $params[] = $championshipId;
            $params[] = $groupId;
        }
        $sql .= ' ORDER BY t.name';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function groupTeam(int $groupId, int $teamId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM group_teams WHERE group_id = ? AND team_id = ? LIMIT 1');
        $statement->execute([$groupId, $teamId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function addTeam(int $phaseId, int $groupId, int $teamId, ?int $position): void
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO group_teams (phase_id, group_id, team_id, position, status, joined_at, updated_at) VALUES (?, ?, ?, ?, \'active\', ?, ?) ON DUPLICATE KEY UPDATE group_id = VALUES(group_id), position = VALUES(position), status = \'active\', withdrawn_at = NULL, updated_at = VALUES(updated_at)');
        $statement->execute([$phaseId, $groupId, $teamId, $position, $now, $now]);
    }

    public function withdrawTeam(int $groupId, int $teamId): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare("UPDATE group_teams SET status = 'withdrawn', withdrawn_at = ?, updated_at = ? WHERE group_id = ? AND team_id = ? AND status = 'active'")->execute([$now, $now, $groupId, $teamId]);
    }

    public function updateGroupTeam(int $groupId, int $teamId, int $targetGroupId, int $position): void
    {
        $this->pdo->prepare('UPDATE group_teams SET group_id = ?, position = ?, status = \'active\', withdrawn_at = NULL, updated_at = ? WHERE group_id = ? AND team_id = ?')->execute([$targetGroupId, $position, date('Y-m-d H:i:s'), $groupId, $teamId]);
    }

    public function listVenues(int $championshipId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM venues WHERE championship_id = ? AND deleted_at IS NULL ORDER BY status, name');
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function createVenue(array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO venues (championship_id, name, address, city, state, capacity, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$data['championship_id'], $data['name'], $data['address'] ?: null, $data['city'] ?: null, $data['state'] ?: null, $data['capacity'] ?: null, $data['status'] ?: 'active', $userId, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listRounds(int $phaseId, ?int $groupId = null): array
    {
        $sql = 'SELECT r.*, g.name AS group_name FROM competition_rounds r INNER JOIN competition_groups g ON g.id = r.group_id WHERE r.phase_id = ?';
        $params = [$phaseId];
        if ($groupId !== null) { $sql .= ' AND r.group_id = ?'; $params[] = $groupId; }
        $sql .= ' ORDER BY r.group_id, r.round_number';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function round(int $phaseId, int $groupId, int $roundNumber): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM competition_rounds WHERE phase_id = ? AND group_id = ? AND round_number = ? LIMIT 1');
        $statement->execute([$phaseId, $groupId, $roundNumber]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function createRound(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO competition_rounds (phase_id, group_id, round_number, period_start, period_end, status, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE period_start = VALUES(period_start), period_end = VALUES(period_end), status = VALUES(status), published_at = VALUES(published_at), updated_at = VALUES(updated_at)');
        $statement->execute([$data['phase_id'], $data['group_id'], $data['round_number'], $data['period_start'], $data['period_end'], $data['status'] ?: 'published', $data['status'] === 'published' ? $now : null, $now, $now]);
        return (int) ($this->pdo->lastInsertId() ?: $this->round((int) $data['phase_id'], (int) $data['group_id'], (int) $data['round_number'])['id']);
    }

    public function listMatches(int $userId, string $scope, array $filters = []): array
    {
        [$scopeSql, $scopeParams] = $this->matchScope($userId, $scope);
        $conditions = [$scopeSql];
        $params = $scopeParams;
        foreach (['championship_id' => 'm.championship_id', 'phase_id' => 'm.phase_id', 'group_id' => 'm.group_id', 'round_id' => 'm.round_id', 'round_number' => 'r.round_number', 'status' => 'm.status'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') { $conditions[] = $column . ' = ?'; $params[] = $filters[$key]; }
        }
        if (($filters['team_id'] ?? '') !== '') { $conditions[] = '(m.home_team_id = ? OR m.away_team_id = ?)'; $params[] = $filters['team_id']; $params[] = $filters['team_id']; }
        if (!empty($filters['from'])) { $conditions[] = 'm.match_date >= ?'; $params[] = $filters['from']; }
        if (!empty($filters['to'])) { $conditions[] = 'm.match_date <= ?'; $params[] = $filters['to']; }
        if (!empty($filters['upcoming'])) $conditions[] = "m.match_date >= CURDATE() AND m.status IN ('scheduled', 'confirmed', 'postponed')";
        $sql = $this->matchSelect() . ' WHERE ' . implode(' AND ', $conditions) . ' ORDER BY m.match_date IS NULL, m.match_date, m.match_time, m.id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function matchForUser(int $id, int $userId, string $scope): ?array
    {
        [$scopeSql, $scopeParams] = $this->matchScope($userId, $scope);
        $statement = $this->pdo->prepare($this->matchSelect() . ' WHERE m.id = ? AND ' . $scopeSql . ' LIMIT 1');
        $statement->execute(array_merge([$id], $scopeParams));
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function matchById(int $id): ?array
    {
        $statement = $this->pdo->prepare($this->matchSelect() . ' WHERE m.id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function createMatch(array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO matches (championship_id, phase_id, group_id, round_id, home_team_id, away_team_id, venue_id, fixture_key, leg_number, match_order, match_date, match_time, status, observation, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $statement->execute([$data['championship_id'], $data['phase_id'], $data['group_id'], $data['round_id'], $data['home_team_id'], $data['away_team_id'], $data['venue_id'] ?: null, $data['fixture_key'], $data['leg_number'], $data['match_order'], $data['match_date'], $data['match_time'], $data['status'] ?: 'scheduled', $data['observation'] ?: null, $userId, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function matchByFixture(string $fixtureKey): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM matches WHERE fixture_key = ? LIMIT 1');
        $statement->execute([$fixtureKey]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function updateMatchAgenda(int $id, array $data): void
    {
        $this->pdo->prepare('UPDATE matches SET match_date = ?, match_time = ?, venue_id = ?, status = ?, observation = ?, updated_at = ? WHERE id = ?')->execute([$data['match_date'] ?: null, $data['match_time'] ?: null, $data['venue_id'] ?: null, $data['status'], $data['observation'] ?? null, date('Y-m-d H:i:s'), $id]);
    }

    public function recordScheduleChange(int $matchId, array $data, int $userId): void
    {
        $this->pdo->prepare('INSERT INTO match_schedule_changes (match_id, action, from_date, from_time, from_venue_id, to_date, to_time, to_venue_id, reason, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([$matchId, $data['action'], $data['from_date'], $data['from_time'], $data['from_venue_id'], $data['to_date'], $data['to_time'], $data['to_venue_id'], $data['reason'], $userId, date('Y-m-d H:i:s')]);
    }

    public function scheduleChanges(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT sc.*, u.name AS user_name, v1.name AS from_venue_name, v2.name AS to_venue_name FROM match_schedule_changes sc INNER JOIN users u ON u.id = sc.changed_by LEFT JOIN venues v1 ON v1.id = sc.from_venue_id LEFT JOIN venues v2 ON v2.id = sc.to_venue_id WHERE sc.match_id = ? ORDER BY sc.created_at, sc.id');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function createDecision(array $data, int $userId): int
    {
        $statement = $this->pdo->prepare('INSERT INTO administrative_decisions (championship_id, phase_id, group_id, match_id, team_id, decision_type, status, notes, decided_by, decided_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$data['championship_id'], $data['phase_id'] ?: null, $data['group_id'] ?: null, $data['match_id'] ?: null, $data['team_id'] ?: null, $data['decision_type'], $data['status'] ?: 'recorded', $data['notes'], $userId, date('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    public function decisions(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT d.*, u.name AS user_name FROM administrative_decisions d INNER JOIN users u ON u.id = d.decided_by WHERE d.match_id = ? ORDER BY d.decided_at DESC, d.id DESC');
        $statement->execute([$matchId]);
        return $statement->fetchAll();
    }

    public function hasConflict(array $match, ?int $exceptId = null): bool
    {
        if (empty($match['match_date']) || empty($match['match_time'])) return false;
        $sql = "SELECT id FROM matches WHERE championship_id = ? AND match_date = ? AND match_time = ? AND status NOT IN ('cancelled', 'finished', 'homologated') AND (home_team_id IN (?, ?) OR away_team_id IN (?, ?) OR (? IS NOT NULL AND venue_id = ?))";
        $params = [$match['championship_id'], $match['match_date'], $match['match_time'], $match['home_team_id'], $match['away_team_id'], $match['home_team_id'], $match['away_team_id'], $match['venue_id'] ?: null, $match['venue_id'] ?: null];
        if ($exceptId !== null) { $sql .= ' AND id <> ?'; $params[] = $exceptId; }
        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return (bool) $statement->fetchColumn();
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

    private function matchSelect(): string
    {
        return 'SELECT m.*, c.name AS championship_name, p.name AS phase_name, g.name AS group_name, r.round_number, ht.name AS home_team_name, ht.short_name AS home_team_short_name, at.name AS away_team_name, at.short_name AS away_team_short_name, v.name AS venue_name FROM matches m INNER JOIN championships c ON c.id = m.championship_id INNER JOIN competition_phases p ON p.id = m.phase_id INNER JOIN competition_groups g ON g.id = m.group_id INNER JOIN competition_rounds r ON r.id = m.round_id INNER JOIN teams ht ON ht.id = m.home_team_id INNER JOIN teams at ON at.id = m.away_team_id LEFT JOIN venues v ON v.id = m.venue_id';
    }

    private function matchScope(int $userId, string $scope): array
    {
        if ($scope === 'administrator') return ['1 = 1', []];
        if ($scope === 'organizer') return ['EXISTS (SELECT 1 FROM championship_user_assignments cua WHERE cua.championship_id = m.championship_id AND cua.user_id = ? AND cua.assignment_type = \'organizer\')', [$userId]];
        if ($scope === 'team') return ["EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.user_id = ? AND tua.status = 'active' AND tua.assignment_type IN ('manager', 'head_coach') AND (tua.team_id = m.home_team_id OR tua.team_id = m.away_team_id))", [$userId]];
        return ['0 = 1', []];
    }
}
