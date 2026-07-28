<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AthleteDocumentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listForAthlete(int $athleteId): array
    {
        $statement = $this->pdo->prepare('SELECT d.*, dt.`key` AS document_type_key, dt.name AS document_type_name, g.full_name AS guardian_name, u.name AS reviewer_name FROM athlete_documents d INNER JOIN athlete_document_types dt ON dt.id = d.document_type_id LEFT JOIN legal_guardians g ON g.id = d.guardian_id LEFT JOIN users u ON u.id = d.reviewed_by WHERE d.athlete_id = ? AND d.deleted_at IS NULL ORDER BY d.created_at DESC, d.id DESC');
        $statement->execute([$athleteId]);
        return $statement->fetchAll();
    }

    public function findForAthlete(int $id, int $athleteId): ?array
    {
        $statement = $this->pdo->prepare('SELECT d.*, dt.name AS document_type_name, g.full_name AS guardian_name FROM athlete_documents d INNER JOIN athlete_document_types dt ON dt.id = d.document_type_id LEFT JOIN legal_guardians g ON g.id = d.guardian_id WHERE d.id = ? AND d.athlete_id = ? AND d.deleted_at IS NULL LIMIT 1');
        $statement->execute([$id, $athleteId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data, int $createdBy): int
    {
        $statement = $this->pdo->prepare('INSERT INTO athlete_documents (athlete_id, guardian_id, document_type_id, storage_path, original_name, mime_type, size_bytes, expires_at, status, observation, rejection_reason, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'pending\', ?, NULL, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$data['athlete_id'], $data['guardian_id'] ?: null, $data['document_type_id'], $data['storage_path'], $data['original_name'], $data['mime_type'], $data['size_bytes'], $data['expires_at'] ?: null, $data['observation'], $createdBy, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function review(int $id, string $status, ?string $reason, int $reviewedBy): void
    {
        $statement = $this->pdo->prepare('UPDATE athlete_documents SET status = ?, rejection_reason = ?, reviewed_by = ?, reviewed_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$status, $reason ?: null, $reviewedBy, $now, $now, $id]);
    }

    public function softDelete(int $id): void
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare('UPDATE athlete_documents SET status = \'archived\', deleted_at = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL')->execute([$now, $now, $id]);
    }
}
