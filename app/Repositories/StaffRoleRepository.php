<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StaffRoleRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listActive(): array
    {
        return $this->pdo->query('SELECT * FROM staff_roles WHERE active = 1 ORDER BY display_order, name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM staff_roles WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }
}
