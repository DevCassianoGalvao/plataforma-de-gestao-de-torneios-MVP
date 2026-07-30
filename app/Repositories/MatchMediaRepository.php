<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MatchMediaRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(int $matchId): array
    {
        $statement = $this->pdo->prepare('SELECT mm.*, u.name AS uploader_name FROM match_media mm INNER JOIN users u ON u.id = mm.uploaded_by WHERE mm.match_id = ? AND mm.deleted_at IS NULL ORDER BY mm.created_at DESC, mm.id DESC');
        $statement->execute([$matchId]); return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM match_media WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]); return $statement->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO match_media (match_id, championship_id, title, caption, storage_path, original_name, mime_type, visibility, status, captured_at, uploaded_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'approved\', ?, ?, ?, ?)');
        $statement->execute([$data['match_id'], $data['championship_id'], $data['title'], $data['caption'], $data['storage_path'], $data['original_name'], $data['mime_type'], $data['visibility'], $data['captured_at'], $data['uploaded_by'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }
}
