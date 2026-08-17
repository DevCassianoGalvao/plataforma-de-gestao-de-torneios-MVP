<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TeamRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listForUser(int $userId, string $scope, array $filters = []): array
    {
        [$scopeSql, $scopeParams] = $this->scopeSql($userId, $scope, false);
        $conditions = ['t.deleted_at IS NULL', $scopeSql];
        $params = $scopeParams;
        if (($filters['search'] ?? '') !== '') {
            $term = '%' . trim((string) $filters['search']) . '%';
            $conditions[] = '(t.name LIKE ? OR t.short_name LIKE ? OR t.slug LIKE ? OR t.abbreviation LIKE ?)';
            array_push($params, $term, $term, $term, $term);
        }
        foreach (['championship_id' => 't.championship_id', 'status' => 't.status', 'city' => 't.city'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $conditions[] = $column . ' = ?';
                $params[] = $filters[$key];
            }
        }
        $sql = 'SELECT t.*, c.name AS championship_name, c.slug AS championship_slug, c.requires_guardian, cat.name AS category_name, cat.minimum_age, cat.maximum_age, cat.gender_rule, s.name AS season_name, f.name AS formation_name, '
            . 'COALESCE('
            . '(SELECT u.name FROM team_user_assignments a INNER JOIN users u ON u.id = a.user_id WHERE a.team_id = t.id AND a.assignment_type = \'head_coach\' AND a.status = \'active\' AND u.deleted_at IS NULL ORDER BY a.starts_at DESC LIMIT 1), '
            . '(SELECT COALESCE(NULLIF(ts.display_name, \'\'), ts.full_name) FROM team_staff ts INNER JOIN staff_roles sr ON sr.id = ts.staff_role_id WHERE ts.team_id = t.id AND sr.`key` = \'head_coach\' AND ts.status = \'active\' AND ts.deleted_at IS NULL ORDER BY ts.starts_at DESC, ts.id DESC LIMIT 1)'
            . ') AS coach_name, '
            . '(SELECT u.name FROM team_user_assignments a INNER JOIN users u ON u.id = a.user_id WHERE a.team_id = t.id AND a.assignment_type = \'manager\' AND a.status = \'active\' ORDER BY a.starts_at DESC LIMIT 1) AS manager_name '
            . 'FROM teams t INNER JOIN championships c ON c.id = t.championship_id INNER JOIN categories cat ON cat.id = c.category_id INNER JOIN seasons s ON s.id = c.season_id LEFT JOIN tactical_formations f ON f.id = t.default_tactical_formation_id WHERE '
            . implode(' AND ', $conditions) . ' ORDER BY t.name';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findForUser(int $id, int $userId, string $scope, bool $mutation = false): ?array
    {
        [$scopeSql, $scopeParams] = $this->scopeSql($userId, $scope, $mutation);
        $statement = $this->pdo->prepare('SELECT t.*, c.name AS championship_name, c.slug AS championship_slug, c.status AS championship_status, c.requires_guardian, cat.name AS category_name, cat.minimum_age, cat.maximum_age, cat.gender_rule, s.name AS season_name, s.year AS season_year, f.name AS formation_name, ' . $this->leadershipSelectSql() . ' FROM teams t INNER JOIN championships c ON c.id = t.championship_id INNER JOIN categories cat ON cat.id = c.category_id INNER JOIN seasons s ON s.id = c.season_id LEFT JOIN tactical_formations f ON f.id = t.default_tactical_formation_id WHERE t.id = ? AND t.deleted_at IS NULL AND ' . $scopeSql . ' LIMIT 1');
        $statement->execute(array_merge([$id], $scopeParams));
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findForUserBySlug(string $slug, int $userId, string $scope, bool $mutation = false): ?array
    {
        [$scopeSql, $scopeParams] = $this->scopeSql($userId, $scope, $mutation);
        $statement = $this->pdo->prepare('SELECT t.*, c.name AS championship_name, c.slug AS championship_slug, c.status AS championship_status, cat.name AS category_name, cat.minimum_age, cat.maximum_age, cat.gender_rule, s.name AS season_name, s.year AS season_year, f.name AS formation_name, ' . $this->leadershipSelectSql() . ' FROM teams t INNER JOIN championships c ON c.id = t.championship_id INNER JOIN categories cat ON cat.id = c.category_id INNER JOIN seasons s ON s.id = c.season_id LEFT JOIN tactical_formations f ON f.id = t.default_tactical_formation_id WHERE t.slug = ? AND t.deleted_at IS NULL AND ' . $scopeSql . ' LIMIT 1');
        $statement->execute(array_merge([$slug], $scopeParams));
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data, int $createdBy): int
    {
        $statement = $this->pdo->prepare('INSERT INTO teams (championship_id, name, short_name, slug, abbreviation, description, city, state, primary_color, secondary_color, shield_path, status, default_tactical_formation_id, default_formation_changed_at, default_formation_changed_by, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $formation = $data['default_tactical_formation_id'] ?: null;
        $statement->execute([$data['championship_id'], $data['name'], $data['short_name'], $data['slug'], $data['abbreviation'], $data['description'], $data['city'], $data['state'], $data['primary_color'], $data['secondary_color'], $data['shield_path'] ?? null, $data['status'], $formation, $formation ? $now : null, $formation ? $createdBy : null, $createdBy, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateGeneral(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE teams SET name = ?, short_name = ?, slug = ?, abbreviation = ?, description = ?, city = ?, state = ?, status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['name'], $data['short_name'], $data['slug'], $data['abbreviation'], $data['description'], $data['city'], $data['state'], $data['status'], date('Y-m-d H:i:s'), $id]);
    }

    public function updateIdentity(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE teams SET primary_color = ?, secondary_color = ?, shield_path = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['primary_color'], $data['secondary_color'], $data['shield_path'], date('Y-m-d H:i:s'), $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE teams SET status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$status, date('Y-m-d H:i:s'), $id]);
    }

    public function updateDefaultFormation(int $id, int $formationId, int $userId): void
    {
        $statement = $this->pdo->prepare('UPDATE teams SET default_tactical_formation_id = ?, default_formation_changed_at = ?, default_formation_changed_by = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$formationId, $now, $userId, $now, $id]);
    }

    public function assignments(int $teamId): array
    {
        $statement = $this->pdo->prepare('SELECT a.*, u.name, u.email, u.status AS user_status FROM team_user_assignments a INNER JOIN users u ON u.id = a.user_id WHERE a.team_id = ? ORDER BY a.status, a.starts_at DESC, u.name');
        $statement->execute([$teamId]);
        return $statement->fetchAll();
    }

    public function activeAssignmentExists(int $teamId, int $userId, string $type): bool
    {
        $statement = $this->pdo->prepare("SELECT id FROM team_user_assignments WHERE team_id = ? AND user_id = ? AND assignment_type = ? AND status = 'active' LIMIT 1");
        $statement->execute([$teamId, $userId, $type]);
        return (bool) $statement->fetchColumn();
    }

    public function createAssignment(int $teamId, int $userId, string $type, int $createdBy, string $startsAt): int
    {
        $statement = $this->pdo->prepare('INSERT INTO team_user_assignments (team_id, user_id, assignment_type, status, starts_at, created_by, created_at, updated_at) VALUES (?, ?, ?, \'active\', ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$teamId, $userId, $type, $startsAt, $createdBy, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function endAssignment(int $assignmentId, string $endsAt): void
    {
        $statement = $this->pdo->prepare("UPDATE team_user_assignments SET status = 'ended', ends_at = ?, updated_at = ? WHERE id = ? AND status = 'active'");
        $statement->execute([$endsAt, date('Y-m-d H:i:s'), $assignmentId]);
    }

    public function assignment(int $assignmentId): ?array
    {
        $statement = $this->pdo->prepare('SELECT a.*, u.name, u.email FROM team_user_assignments a INNER JOIN users u ON u.id = a.user_id WHERE a.id = ? LIMIT 1');
        $statement->execute([$assignmentId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function activeAssignmentForUser(int $userId, string $type): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM team_user_assignments WHERE user_id = ? AND assignment_type = ? AND status = 'active' ORDER BY starts_at DESC, id DESC LIMIT 1");
        $statement->execute([$userId, $type]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    private function leadershipSelectSql(): string
    {
        return "COALESCE("
            . "(SELECT u.name FROM team_user_assignments a INNER JOIN users u ON u.id = a.user_id WHERE a.team_id = t.id AND a.assignment_type = 'head_coach' AND a.status = 'active' AND u.deleted_at IS NULL ORDER BY a.starts_at DESC LIMIT 1), "
            . "(SELECT COALESCE(NULLIF(ts.display_name, ''), ts.full_name) FROM team_staff ts INNER JOIN staff_roles sr ON sr.id = ts.staff_role_id WHERE ts.team_id = t.id AND sr.`key` = 'head_coach' AND ts.status = 'active' AND ts.deleted_at IS NULL ORDER BY ts.starts_at DESC, ts.id DESC LIMIT 1)"
            . ") AS coach_name, "
            . "(SELECT u.name FROM team_user_assignments a INNER JOIN users u ON u.id = a.user_id WHERE a.team_id = t.id AND a.assignment_type = 'manager' AND a.status = 'active' AND u.deleted_at IS NULL ORDER BY a.starts_at DESC LIMIT 1) AS manager_name";
    }

    private function scopeSql(int $userId, string $scope, bool $mutation): array
    {
        if ($scope === 'administrator') return ['1 = 1', []];
        $types = $mutation ? "'manager', 'head_coach'" : "'manager', 'head_coach', 'assistant_coach', 'viewer'";
        return ["EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.team_id = t.id AND tua.user_id = ? AND tua.assignment_type IN ({$types}) AND tua.status = 'active')", [$userId]];
    }
}
