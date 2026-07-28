<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AthleteRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listForUser(int $userId, string $scope, array $filters = []): array
    {
        [$scopeSql, $scopeParams] = $this->scopeSql($userId, $scope, false);
        $conditions = ['a.deleted_at IS NULL', $scopeSql];
        $params = $scopeParams;
        if (($filters['search'] ?? '') !== '') {
            $term = '%' . trim((string) $filters['search']) . '%';
            $conditions[] = '(a.full_name LIKE ? OR a.sporting_name LIKE ? OR t.name LIKE ?)';
            array_push($params, $term, $term, $term);
        }
        foreach (['team_id' => 'a.team_id', 'status' => 'a.status', 'primary_position_id' => 'a.primary_position_id'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                if ($key === 'primary_position_id') {
                    $conditions[] = '(a.primary_position_id = ? OR EXISTS (SELECT 1 FROM athlete_secondary_positions asp_filter WHERE asp_filter.athlete_id = a.id AND asp_filter.position_id = ?))';
                    $params[] = $filters[$key];
                    $params[] = $filters[$key];
                } else {
                    $conditions[] = $column . ' = ?';
                    $params[] = $filters[$key];
                }
            }
        }
        if (($filters['age_min'] ?? '') !== '') {
            $conditions[] = 'TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) >= ?';
            $params[] = (int) $filters['age_min'];
        }
        if (($filters['age_max'] ?? '') !== '') {
            $conditions[] = 'TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) <= ?';
            $params[] = (int) $filters['age_max'];
        }
        $sql = 'SELECT a.*, TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) AS age, p.name AS primary_position_name, p.`code` AS primary_position_code, t.name AS team_name, t.slug AS team_slug, c.name AS championship_name, c.slug AS championship_slug, cat.name AS category_name FROM athletes a INNER JOIN positions p ON p.id = a.primary_position_id INNER JOIN teams t ON t.id = a.team_id INNER JOIN championships c ON c.id = t.championship_id INNER JOIN categories cat ON cat.id = c.category_id WHERE ' . implode(' AND ', $conditions) . ' ORDER BY a.full_name';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findForUser(int $id, int $userId, string $scope, bool $mutation = false): ?array
    {
        [$scopeSql, $scopeParams] = $this->scopeSql($userId, $scope, $mutation);
        $statement = $this->pdo->prepare('SELECT a.*, TIMESTAMPDIFF(YEAR, a.birth_date, CURDATE()) AS age, p.name AS primary_position_name, p.`code` AS primary_position_code, t.name AS team_name, t.slug AS team_slug, c.name AS championship_name, c.slug AS championship_slug, cat.name AS category_name, cat.minimum_age, cat.maximum_age, cat.gender_rule FROM athletes a INNER JOIN positions p ON p.id = a.primary_position_id INNER JOIN teams t ON t.id = a.team_id INNER JOIN championships c ON c.id = t.championship_id INNER JOIN categories cat ON cat.id = c.category_id WHERE a.id = ? AND a.deleted_at IS NULL AND ' . $scopeSql . ' LIMIT 1');
        $statement->execute(array_merge([$id], $scopeParams));
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data, int $createdBy): int
    {
        $statement = $this->pdo->prepare('INSERT INTO athletes (team_id, full_name, sporting_name, photo_path, birth_date, gender, primary_position_id, preferred_number, dominant_foot, status, private_notes, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$data['team_id'], $data['full_name'], $data['sporting_name'] ?: null, $data['photo_path'] ?: null, $data['birth_date'], $data['gender'] ?: null, $data['primary_position_id'], $data['preferred_number'] ?: null, $data['dominant_foot'] ?: null, $data['status'], $data['private_notes'], $createdBy, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE athletes SET full_name = ?, sporting_name = ?, photo_path = ?, birth_date = ?, gender = ?, primary_position_id = ?, preferred_number = ?, dominant_foot = ?, status = ?, private_notes = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['full_name'], $data['sporting_name'] ?: null, $data['photo_path'] ?: null, $data['birth_date'], $data['gender'] ?: null, $data['primary_position_id'], $data['preferred_number'] ?: null, $data['dominant_foot'] ?: null, $data['status'], $data['private_notes'], date('Y-m-d H:i:s'), $id]);
    }

    public function secondaryPositions(int $athleteId): array
    {
        $statement = $this->pdo->prepare('SELECT p.* FROM athlete_secondary_positions asp INNER JOIN positions p ON p.id = asp.position_id WHERE asp.athlete_id = ? ORDER BY p.display_order, p.name');
        $statement->execute([$athleteId]);
        return $statement->fetchAll();
    }

    public function syncSecondaryPositions(int $athleteId, array $positionIds): void
    {
        $this->pdo->prepare('DELETE FROM athlete_secondary_positions WHERE athlete_id = ?')->execute([$athleteId]);
        $statement = $this->pdo->prepare('INSERT INTO athlete_secondary_positions (athlete_id, position_id, created_at) VALUES (?, ?, ?)');
        foreach (array_unique(array_map('intval', $positionIds)) as $positionId) {
            $statement->execute([$athleteId, $positionId, date('Y-m-d H:i:s')]);
        }
    }

    public function duplicateExists(int $teamId, string $fullName, string $birthDate, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM athletes WHERE team_id = ? AND full_name = ? AND birth_date = ? AND deleted_at IS NULL';
        $params = [$teamId, trim($fullName), $birthDate];
        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        return (bool) $statement->fetchColumn();
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE athletes SET status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$status, date('Y-m-d H:i:s'), $id]);
    }

    public function softDelete(int $id): void
    {
        $statement = $this->pdo->prepare("UPDATE athletes SET status = 'archived', deleted_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL");
        $now = date('Y-m-d H:i:s');
        $statement->execute([$now, $now, $id]);
    }

    private function scopeSql(int $userId, string $scope, bool $mutation): array
    {
        if ($scope === 'administrator') return ['1 = 1', []];
        if ($scope === 'organizer') return ['EXISTS (SELECT 1 FROM championship_user_assignments cua WHERE cua.championship_id = t.championship_id AND cua.user_id = ? AND cua.assignment_type = \'organizer\')', [$userId]];
        $types = $mutation ? "'manager', 'head_coach'" : "'manager', 'head_coach', 'assistant_coach', 'viewer'";
        return ["EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.team_id = t.id AND tua.user_id = ? AND tua.assignment_type IN ({$types}) AND tua.status = 'active')", [$userId]];
    }
}
