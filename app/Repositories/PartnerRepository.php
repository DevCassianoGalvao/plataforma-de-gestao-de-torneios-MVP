<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PartnerRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listForChampionship(int $championshipId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM championship_sponsors WHERE championship_id = ? AND deleted_at IS NULL ORDER BY partner_type, display_order, name');
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM championship_sponsors WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO championship_sponsors (championship_id, partner_type, name, website_url, logo_path, display_order, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$data['championship_id'], $data['partner_type'], $data['name'], $data['website_url'] ?: null, $data['logo_path'] ?: null, $data['display_order'], $data['status'], $userId, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE championship_sponsors SET partner_type = ?, name = ?, website_url = ?, logo_path = ?, display_order = ?, status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['partner_type'], $data['name'], $data['website_url'] ?: null, $data['logo_path'] ?: null, $data['display_order'], $data['status'], date('Y-m-d H:i:s'), $id]);
    }

    public function softDelete(int $id): void
    {
        $this->pdo->prepare('UPDATE championship_sponsors SET deleted_at = ?, updated_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
    }
}
