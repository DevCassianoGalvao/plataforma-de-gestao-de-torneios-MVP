<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Services\SensitiveData;
use PDO;

final class GuardianRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listForAthlete(int $athleteId): array
    {
        $statement = $this->pdo->prepare('SELECT g.*, ag.relationship, ag.authorization_status, ag.authorization_note, ag.is_primary, ag.created_at AS linked_at, ' . "'Documento protegido'" . ' AS document_display FROM legal_guardians g INNER JOIN athlete_guardians ag ON ag.guardian_id = g.id WHERE ag.athlete_id = ? AND g.deleted_at IS NULL ORDER BY ag.is_primary DESC, g.full_name');
        $statement->execute([$athleteId]);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT g.*, ' . "'Documento protegido'" . ' AS document_display FROM legal_guardians g WHERE g.id = ? AND g.deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO legal_guardians (full_name, phone, email, document_ciphertext, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$data['full_name'], $data['phone'], $data['email'] ?: null, SensitiveData::encrypt($data['document_number']), $data['status'], $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE legal_guardians SET full_name = ?, phone = ?, email = ?, document_ciphertext = ?, status = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$data['full_name'], $data['phone'], $data['email'] ?: null, SensitiveData::encrypt($data['document_number']), $data['status'], date('Y-m-d H:i:s'), $id]);
    }

    public function link(int $athleteId, int $guardianId, array $data): void
    {
        $statement = $this->pdo->prepare('INSERT INTO athlete_guardians (athlete_id, guardian_id, relationship, authorization_status, authorization_note, is_primary, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE relationship = VALUES(relationship), authorization_status = VALUES(authorization_status), authorization_note = VALUES(authorization_note), is_primary = VALUES(is_primary), updated_at = VALUES(updated_at)');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$athleteId, $guardianId, $data['relationship'], $data['authorization_status'], $data['authorization_note'], $data['is_primary'] ? 1 : 0, $now, $now]);
    }

    public function unlink(int $athleteId, int $guardianId): void
    {
        $this->pdo->prepare("UPDATE athlete_guardians SET authorization_status = 'revoked', updated_at = ? WHERE athlete_id = ? AND guardian_id = ?")->execute([date('Y-m-d H:i:s'), $athleteId, $guardianId]);
    }

    public function hasActiveForAthlete(int $athleteId): bool
    {
        $statement = $this->pdo->prepare("SELECT 1 FROM athlete_guardians ag INNER JOIN legal_guardians g ON g.id = ag.guardian_id WHERE ag.athlete_id = ? AND ag.authorization_status IN ('pending', 'authorized') AND g.status = 'active' AND g.deleted_at IS NULL LIMIT 1");
        $statement->execute([$athleteId]);
        return (bool) $statement->fetchColumn();
    }

    public function linkedToAthlete(int $athleteId, int $guardianId): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM athlete_guardians WHERE athlete_id = ? AND guardian_id = ? LIMIT 1');
        $statement->execute([$athleteId, $guardianId]);
        return (bool) $statement->fetchColumn();
    }
}
