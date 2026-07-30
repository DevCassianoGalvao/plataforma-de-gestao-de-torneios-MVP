<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use PDO;

final class AuditService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function record(string $action, ?int $userId = null, ?string $resourceType = null, int|string|null $resourceId = null, array $metadata = [], ?Request $request = null): void
    {
        $statement = $this->pdo->prepare('INSERT INTO audit_logs (user_id, action, resource_type, resource_id, metadata, ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([
            $userId,
            $action,
            $resourceType,
            $resourceId === null ? null : (string) $resourceId,
            $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $request?->ip(),
            $request?->userAgent(),
            date('Y-m-d H:i:s'),
        ]);
        $auditId = (int) $this->pdo->lastInsertId();
        try {
            $notification = $this->pdo->prepare('INSERT IGNORE INTO admin_notifications (audit_id, title, message, created_at) VALUES (?, ?, ?, ?)');
            $label = str_replace(['.', '_'], [' / ', ' '], $action);
            $notification->execute([$auditId, 'Atividade do sistema', 'Evento registrado: ' . $label, date('Y-m-d H:i:s')]);
        } catch (\PDOException) {
            // Permite operar durante deploy antes da migration de notificacoes.
        }
    }

    public function recent(int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
        return $this->pdo->query('SELECT a.*, u.name AS user_name, u.email AS user_email FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC, a.id DESC LIMIT ' . $limit)->fetchAll();
    }
}
