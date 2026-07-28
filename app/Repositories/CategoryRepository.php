<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(bool $includeInactive = false): array
    {
        $where = $includeInactive ? 'deleted_at IS NULL' : "deleted_at IS NULL AND status = 'active'";
        return $this->pdo->query('SELECT * FROM categories WHERE ' . $where . ' ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM categories WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO categories (name, slug, description, minimum_age, maximum_age, gender_rule, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$data['name'], $data['slug'], $data['description'], $data['minimum_age'], $data['maximum_age'], $data['gender_rule'], $data['status'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE categories SET name = ?, slug = ?, description = ?, minimum_age = ?, maximum_age = ?, gender_rule = ?, status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['name'], $data['slug'], $data['description'], $data['minimum_age'], $data['maximum_age'], $data['gender_rule'], $data['status'], date('Y-m-d H:i:s'), $id]);
    }

    public function status(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE categories SET status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$status, date('Y-m-d H:i:s'), $id]);
    }
}
