<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OfficialRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listForChampionship(int $championshipId, bool $publicOnly = false): array
    {
        $sql = 'SELECT id, championship_id, full_name, public_name, role, photo_path, status, created_at FROM championship_officials WHERE championship_id = ? AND deleted_at IS NULL';
        if ($publicOnly) $sql .= " AND status = 'active'";
        $statement = $this->pdo->prepare($sql . ' ORDER BY full_name, id');
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM championship_officials WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO championship_officials (championship_id, full_name, public_name, role, photo_path, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$data['championship_id'], $data['full_name'], $data['public_name'] ?: null, $data['role'], $data['photo_path'] ?: null, $data['status'], $userId, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE championship_officials SET full_name = ?, public_name = ?, role = ?, photo_path = ?, status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['full_name'], $data['public_name'] ?: null, $data['role'], $data['photo_path'] ?: null, $data['status'], date('Y-m-d H:i:s'), $id]);
    }

    public function asset(int $id): ?array
    {
        return $this->find($id);
    }
}
