<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TeamStaffRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(int $teamId, array $filters = []): array
    {
        $conditions = ['ts.team_id = ?', 'ts.deleted_at IS NULL'];
        $params = [$teamId];
        foreach (['staff_role_id' => 'ts.staff_role_id', 'status' => 'ts.status'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $conditions[] = $column . ' = ?';
                $params[] = $filters[$key];
            }
        }
        $statement = $this->pdo->prepare('SELECT ts.*, sr.name AS role_name, sr.`key` AS role_key, u.name AS user_name, u.email AS user_email FROM team_staff ts INNER JOIN staff_roles sr ON sr.id = ts.staff_role_id LEFT JOIN users u ON u.id = ts.user_id WHERE ' . implode(' AND ', $conditions) . ' ORDER BY sr.display_order, ts.full_name');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT ts.*, sr.name AS role_name, sr.`key` AS role_key FROM team_staff ts INNER JOIN staff_roles sr ON sr.id = ts.staff_role_id WHERE ts.id = ? AND ts.deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(int $teamId, array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO team_staff (team_id, staff_role_id, user_id, full_name, display_name, email, phone, document_number, photo_path, registration_number, status, starts_at, ends_at, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$teamId, $data['staff_role_id'], $data['user_id'] ?: null, $data['full_name'], $data['display_name'], $data['email'] ?: null, $data['phone'], $data['document_number'], $data['photo_path'] ?? null, $data['registration_number'], $data['status'], $data['starts_at'] ?: null, $data['ends_at'] ?: null, $data['notes'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE team_staff SET staff_role_id = ?, user_id = ?, full_name = ?, display_name = ?, email = ?, phone = ?, document_number = ?, photo_path = ?, registration_number = ?, status = ?, starts_at = ?, ends_at = ?, notes = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['staff_role_id'], $data['user_id'] ?: null, $data['full_name'], $data['display_name'], $data['email'] ?: null, $data['phone'], $data['document_number'], $data['photo_path'] ?? null, $data['registration_number'], $data['status'], $data['starts_at'] ?: null, $data['ends_at'] ?: null, $data['notes'], date('Y-m-d H:i:s'), $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE team_staff SET status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$status, date('Y-m-d H:i:s'), $id]);
    }
}
