<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RetentionRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function policies(): array
    {
        return $this->pdo->query('SELECT * FROM retention_policies ORDER BY scope_key')->fetchAll();
    }

    public function policy(string $scope): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM retention_policies WHERE scope_key = ? LIMIT 1');
        $statement->execute([$scope]);
        return $statement->fetch() ?: null;
    }

    public function savePolicy(string $scope, array $data, int $userId): void
    {
        $days = ($data['retention_days'] ?? '') === '' ? null : max(1, min(36500, (int) $data['retention_days']));
        $statement = $this->pdo->prepare('UPDATE retention_policies SET retention_days = ?, allow_archive = ?, allow_restore = ?, allow_soft_delete = ?, updated_by = ?, updated_at = ? WHERE scope_key = ?');
        $statement->execute([$days, !empty($data['allow_archive']) ? 1 : 0, !empty($data['allow_restore']) ? 1 : 0, !empty($data['allow_soft_delete']) ? 1 : 0, $userId, date('Y-m-d H:i:s'), $scope]);
    }

    public function log(string $scope, string $entity, int $entityId, string $action, ?string $previous, ?string $new, string $reason, int $userId, array $metadata = []): void
    {
        $statement = $this->pdo->prepare('INSERT INTO retention_actions (scope_key, entity_type, entity_id, action, previous_status, new_status, reason, metadata, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$scope, $entity, $entityId, $action, $previous, $new, $reason, json_encode($metadata, JSON_UNESCAPED_UNICODE), $userId, date('Y-m-d H:i:s')]);
    }

    public function lastArchive(string $entity, int $entityId): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM retention_actions WHERE entity_type = ? AND entity_id = ? AND action = 'archive' ORDER BY id DESC LIMIT 1");
        $statement->execute([$entity, $entityId]);
        return $statement->fetch() ?: null;
    }

    public function actions(int $limit = 30): array
    {
        $statement = $this->pdo->prepare('SELECT a.*, u.name AS user_name FROM retention_actions a INNER JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC, a.id DESC LIMIT ' . max(1, min(100, $limit)));
        $statement->execute();
        return $statement->fetchAll();
    }
}
