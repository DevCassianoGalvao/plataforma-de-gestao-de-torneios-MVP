<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ContactRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(?int $championshipId, array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO public_contact_messages (championship_id, name, email, phone, subject, message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, \'new\', ?, ?)');
        $statement->execute([$championshipId, $data['name'], $data['email'], $data['phone'] ?: null, $data['subject'], $data['message'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function list(int $limit = 100): array
    {
        $statement = $this->pdo->query('SELECT cm.*, c.name AS championship_name, u.name AS handler_name FROM public_contact_messages cm LEFT JOIN championships c ON c.id = cm.championship_id LEFT JOIN users u ON u.id = cm.handled_by ORDER BY FIELD(cm.status, \'new\', \'in_progress\', \'resolved\', \'archived\'), cm.created_at DESC, cm.id DESC LIMIT ' . max(1, min(200, $limit)));
        return $statement->fetchAll();
    }

    public function updateStatus(int $id, string $status, int $userId): bool
    {
        $statement = $this->pdo->prepare('UPDATE public_contact_messages SET status = ?, handled_by = ?, handled_at = ?, updated_at = ? WHERE id = ?');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$status, $userId, $now, $now, $id]);
        return $statement->rowCount() > 0;
    }
}
