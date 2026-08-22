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

    public function listForReview(int $userId, string $scope, array $filters = []): array
    {
        $where = ['d.deleted_at IS NULL'];
        $params = [];
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'd.status = ?';
            $params[] = (string) $filters['status'];
        }
        if ($scope === 'team') {
            $where[] = 'EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.team_id = a.team_id AND tua.user_id = ? AND tua.status = \'active\')';
            $params[] = $userId;
        }
        $sql = 'SELECT d.*, dt.name AS document_type_name, a.full_name AS athlete_name, a.sporting_name, t.name AS team_name, u.name AS reviewer_name FROM athlete_documents d INNER JOIN athlete_document_types dt ON dt.id = d.document_type_id INNER JOIN athletes a ON a.id = d.athlete_id AND a.deleted_at IS NULL INNER JOIN teams t ON t.id = a.team_id LEFT JOIN users u ON u.id = d.reviewed_by WHERE ' . implode(' AND ', $where) . ' ORDER BY CASE WHEN d.status = \'pending\' THEN 0 ELSE 1 END, d.created_at ASC, d.id ASC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findForReview(int $id, int $userId, string $scope): ?array
    {
        $where = ['d.id = ?', 'd.deleted_at IS NULL'];
        $params = [$id];
        if ($scope === 'team') {
            $where[] = 'EXISTS (SELECT 1 FROM team_user_assignments tua WHERE tua.team_id = a.team_id AND tua.user_id = ? AND tua.status = \'active\')';
            $params[] = $userId;
        }
        $statement = $this->pdo->prepare('SELECT d.*, a.full_name AS athlete_name, a.sporting_name, t.name AS team_name FROM athlete_documents d INNER JOIN athletes a ON a.id = d.athlete_id AND a.deleted_at IS NULL INNER JOIN teams t ON t.id = a.team_id WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
        $statement->execute($params);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function hasValidAthleteDocument(int $athleteId): bool
    {
        $statement = $this->pdo->prepare("SELECT 1 FROM athlete_documents d INNER JOIN athlete_document_types dt ON dt.id = d.document_type_id WHERE d.athlete_id = ? AND d.id = (SELECT latest.id FROM athlete_documents latest INNER JOIN athlete_document_types latest_type ON latest_type.id = latest.document_type_id WHERE latest.athlete_id = d.athlete_id AND latest_type.`key` = 'athlete_document' AND latest.deleted_at IS NULL ORDER BY latest.created_at DESC, latest.id DESC LIMIT 1) AND d.status = 'approved' AND d.deleted_at IS NULL AND (d.expires_at IS NULL OR d.expires_at >= CURDATE()) LIMIT 1");
        $statement->execute([$athleteId]);
        return (bool) $statement->fetchColumn();
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
