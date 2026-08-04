<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MatchPublicationRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(int $matchId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM match_publications WHERE match_id = ? LIMIT 1');
        $statement->execute([$matchId]);
        return $statement->fetch() ?: null;
    }

    public function save(int $matchId, string $status, ?string $scheduledAt, ?int $publishedBy, ?string $reason = null): void
    {
        $now = date('Y-m-d H:i:s');
        $publishedAt = $status === 'published' ? $now : null;
        $cancelledAt = $status === 'cancelled' ? $now : null;
        $statement = $this->pdo->prepare("INSERT INTO match_publications (match_id, status, scheduled_at, published_at, published_by, cancelled_at, cancelled_by, reason, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), scheduled_at = VALUES(scheduled_at), published_at = VALUES(published_at), published_by = VALUES(published_by), cancelled_at = VALUES(cancelled_at), cancelled_by = VALUES(cancelled_by), reason = VALUES(reason), updated_at = VALUES(updated_at)");
        $statement->execute([$matchId, $status, $scheduledAt, $publishedAt, $publishedBy, $cancelledAt, $status === 'cancelled' ? $publishedBy : null, $reason, $now, $now]);
    }

    public function publishDue(): array
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare("UPDATE match_publications SET status = 'published', published_at = COALESCE(published_at, ?), updated_at = ? WHERE status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= ?");
        $statement->execute([$now, $now, $now]);
        return ['published' => $statement->rowCount(), 'at' => $now];
    }
}
