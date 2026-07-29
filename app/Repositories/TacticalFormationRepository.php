<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TacticalFormationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listActive(): array
    {
        return $this->pdo->query('SELECT * FROM tactical_formations WHERE active = 1 ORDER BY name')->fetchAll();
    }

    public function findWithSlots(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM tactical_formations WHERE id = ? AND active = 1 LIMIT 1');
        $statement->execute([$id]);
        $formation = $statement->fetch();
        if (!$formation) return null;
        $slots = $this->pdo->prepare('SELECT * FROM tactical_formation_slots WHERE tactical_formation_id = ? ORDER BY display_order, id');
        $slots->execute([$id]);
        $formation['slots'] = $slots->fetchAll();
        return $formation;
    }
}
