<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class NotificationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listForUser(int $userId, int $limit = 80): array
    {
        $limit = max(1, min(200, $limit));
        $statement = $this->pdo->prepare('SELECT n.*, r.read_at, a.action, a.resource_type, a.resource_id, u.name AS actor_name FROM admin_notifications n INNER JOIN audit_logs a ON a.id = n.audit_id LEFT JOIN admin_notification_reads r ON r.notification_id = n.id AND r.user_id = ? LEFT JOIN users u ON u.id = a.user_id ORDER BY n.created_at DESC, n.id DESC LIMIT ' . $limit);
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM admin_notifications n LEFT JOIN admin_notification_reads r ON r.notification_id = n.id AND r.user_id = ? WHERE r.notification_id IS NULL');
        $statement->execute([$userId]);
        return (int) $statement->fetchColumn();
    }

    public function markRead(int $notificationId, int $userId): void
    {
        $statement = $this->pdo->prepare('INSERT INTO admin_notification_reads (notification_id, user_id, read_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)');
        $statement->execute([$notificationId, $userId, date('Y-m-d H:i:s')]);
    }

    public function markAllRead(int $userId): void
    {
        $statement = $this->pdo->prepare('INSERT IGNORE INTO admin_notification_reads (notification_id, user_id, read_at) SELECT n.id, ?, ? FROM admin_notifications n LEFT JOIN admin_notification_reads r ON r.notification_id = n.id AND r.user_id = ? WHERE r.notification_id IS NULL');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$userId, $now, $userId]);
    }
}
