<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PositionRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : " WHERE status = 'active'";
        return $this->pdo->query('SELECT * FROM positions' . $where . ' ORDER BY display_order, name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM positions WHERE id = ? AND status = \'active\' LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function ids(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) return [];
        $statement = $this->pdo->prepare('SELECT id FROM positions WHERE status = \'active\' AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $statement->execute($ids);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }
}
