<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AthleteDocumentTypeRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function list(bool $includeInactive = false): array
    {
        $where = $includeInactive ? '' : ' WHERE active = 1';
        return $this->pdo->query('SELECT * FROM athlete_document_types' . $where . ' ORDER BY display_order, name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM athlete_document_types WHERE id = ? AND active = 1 LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }
}
