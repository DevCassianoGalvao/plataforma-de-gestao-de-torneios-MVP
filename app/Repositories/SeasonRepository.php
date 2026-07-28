<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SeasonRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(bool $includeArchived = false): array
    {
        $where = $includeArchived ? 'deleted_at IS NULL' : "deleted_at IS NULL AND status <> 'archived'";
        return $this->pdo->query('SELECT * FROM seasons WHERE ' . $where . ' ORDER BY year DESC, name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM seasons WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO seasons (name, year, starts_at, ends_at, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$data['name'], $data['year'], $data['starts_at'], $data['ends_at'], $data['status'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE seasons SET name = ?, year = ?, starts_at = ?, ends_at = ?, status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['name'], $data['year'], $data['starts_at'], $data['ends_at'], $data['status'], date('Y-m-d H:i:s'), $id]);
    }

    public function status(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE seasons SET status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$status, date('Y-m-d H:i:s'), $id]);
    }
}
