<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ChampionshipCarouselRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listForChampionship(int $championshipId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM championship_carousel_slides WHERE championship_id = ?';
        if ($onlyActive) $sql .= ' AND is_active = 1';
        $sql .= ' ORDER BY display_order, id';
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$championshipId]);
        return $statement->fetchAll();
    }

    public function findForChampionship(int $id, int $championshipId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM championship_carousel_slides WHERE id = ? AND championship_id = ? LIMIT 1');
        $statement->execute([$id, $championshipId]);
        return $statement->fetch() ?: null;
    }

    public function create(int $championshipId, string $title, ?string $linkUrl, string $imagePath, int $displayOrder): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO championship_carousel_slides (championship_id, title, link_url, image_path, display_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?)');
        $statement->execute([$championshipId, $title, $linkUrl, $imagePath, max(0, $displayOrder), $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id, int $championshipId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM championship_carousel_slides WHERE id = ? AND championship_id = ?');
        $statement->execute([$id, $championshipId]);
    }
}
