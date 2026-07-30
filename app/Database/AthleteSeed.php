<?php
declare(strict_types=1);

namespace App\Database;

use App\Services\SensitiveData;
use PDO;

final class AthleteSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $teams = $pdo->query("SELECT t.id, t.short_name FROM teams t INNER JOIN championships c ON c.id = t.championship_id WHERE c.slug = 'copa-brasil-de-talentos-2026' AND t.deleted_at IS NULL ORDER BY t.id LIMIT 10")->fetchAll();
        if (count($teams) < 10) throw new \RuntimeException('As equipes necessarias ao seed de atletas nao foram encontradas.');
        $adminId = self::userId($pdo, 'admin@torneios.local');
        $positions = self::idMap($pdo, 'positions', 'code');
        $documentTypes = self::idMap($pdo, 'athlete_document_types', 'key');
        $statuses = ['active', 'active', 'draft', 'inactive', 'blocked', 'transferred', 'archived', 'active', 'active', 'active'];
        $positionCodes = ['goalkeeper', 'center_back', 'right_back', 'left_back', 'defensive_midfielder', 'central_midfielder', 'attacking_midfielder', 'right_winger', 'left_winger', 'center_forward'];
        $isTest = getenv('APP_ENV') === 'test';
        $now = date('Y-m-d H:i:s');
        foreach ($teams as $teamIndex => $team) {
            for ($slot = 1; $slot <= 2; $slot++) {
                $name = 'Atleta ' . $team['short_name'] . ' ' . $slot;
                $birthDate = getenv('APP_ENV') === 'test'
                    ? ($slot === 1 ? '2012-03-' . str_pad((string) ($teamIndex + 1), 2, '0', STR_PAD_LEFT) : '2013-10-' . str_pad((string) ($teamIndex + 10), 2, '0', STR_PAD_LEFT))
                    : ($slot === 1 ? '2000-03-' . str_pad((string) ($teamIndex + 1), 2, '0', STR_PAD_LEFT) : '2001-10-' . str_pad((string) ($teamIndex + 10), 2, '0', STR_PAD_LEFT));
                $find = $pdo->prepare('SELECT id FROM athletes WHERE team_id = ? AND full_name = ? AND deleted_at IS NULL LIMIT 1');
                $find->execute([(int) $team['id'], $name]);
                $athleteId = (int) $find->fetchColumn();
                $positionCode = $slot === 1 ? 'goalkeeper' : $positionCodes[$teamIndex % count($positionCodes)];
                if (!$athleteId) {
                    $insert = $pdo->prepare('INSERT INTO athletes (team_id, full_name, sporting_name, birth_date, gender, primary_position_id, preferred_number, dominant_foot, status, private_notes, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, \'male\', ?, ?, ?, ?, ?, ?, ?, ?)');
                    $insert->execute([(int) $team['id'], $name, 'Esportivo ' . $team['short_name'] . ' ' . $slot, $birthDate, $positions[$positionCode], $slot, $slot === 1 ? 'right' : 'left', $statuses[$teamIndex], 'Registro ficticio da Etapa 5.', $adminId, $now, $now]);
                    $athleteId = (int) $pdo->lastInsertId();
                }
                if ($isTest) $pdo->prepare('UPDATE athletes SET birth_date = ?, gender = \'male\', status = ?, deleted_at = NULL, updated_at = ? WHERE id = ?')->execute([$birthDate, $statuses[$teamIndex], $now, $athleteId]);
                $secondary = $positions[$positionCodes[($teamIndex + $slot) % count($positionCodes)]];
                $pdo->prepare('INSERT IGNORE INTO athlete_secondary_positions (athlete_id, position_id, created_at) VALUES (?, ?, ?)')->execute([$athleteId, $secondary, $now]);
                if (\App\Services\AthleteRules::isMinor($birthDate)) {
                    $guardianId = self::guardian($pdo, $athleteId, $name, $now);
                    self::document($pdo, $athleteId, $guardianId, $documentTypes['guardian_authorization'], $teamIndex, $now, $adminId);
                }
            }
        }
    }

    private static function guardian(PDO $pdo, int $athleteId, string $athleteName, string $now): int
    {
        $find = $pdo->prepare('SELECT g.id FROM legal_guardians g INNER JOIN athlete_guardians ag ON ag.guardian_id = g.id WHERE ag.athlete_id = ? AND g.deleted_at IS NULL LIMIT 1');
        $find->execute([$athleteId]);
        $existing = (int) $find->fetchColumn();
        if ($existing) return $existing;
        $insert = $pdo->prepare('INSERT INTO legal_guardians (full_name, phone, email, document_ciphertext, status, created_at, updated_at) VALUES (?, ?, ?, ?, \'active\', ?, ?)');
        $insert->execute(['Responsavel de ' . $athleteName, '(11) 99999-0000', strtolower(str_replace(' ', '.', $athleteName)) . '@familia.local', SensitiveData::encrypt('DOC-' . substr(sha1($athleteName), 0, 10)), $now, $now]);
        $guardianId = (int) $pdo->lastInsertId();
        $link = $pdo->prepare('INSERT INTO athlete_guardians (athlete_id, guardian_id, relationship, authorization_status, authorization_note, is_primary, created_at, updated_at) VALUES (?, ?, \'Responsavel legal\', \'authorized\', \'Autorizacao ficticia para testes.\', 1, ?, ?)');
        $link->execute([$athleteId, $guardianId, $now, $now]);
        return $guardianId;
    }

    private static function document(PDO $pdo, int $athleteId, int $guardianId, int $typeId, int $index, string $now, int $adminId): void
    {
        $find = $pdo->prepare('SELECT id FROM athlete_documents WHERE athlete_id = ? AND document_type_id = ? AND deleted_at IS NULL LIMIT 1');
        $find->execute([$athleteId, $typeId]);
        if ($find->fetchColumn()) return;
        $directory = dirname(__DIR__, 2) . '/storage/private/athletes-seed';
        if (!is_dir($directory)) mkdir($directory, 0750, true);
        $filename = 'guardian-' . $athleteId . '.pdf';
        $absolute = $directory . '/' . $filename;
        if (!is_file($absolute)) file_put_contents($absolute, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
        $statuses = ['approved', 'pending', 'rejected', 'expired', 'replaced'];
        $status = $statuses[$index % count($statuses)];
        $insert = $pdo->prepare('INSERT INTO athlete_documents (athlete_id, guardian_id, document_type_id, storage_path, original_name, mime_type, size_bytes, expires_at, status, observation, rejection_reason, reviewed_by, reviewed_at, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, \'application/pdf\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $reviewed = in_array($status, ['approved', 'rejected', 'expired', 'replaced'], true) ? $adminId : null;
        $reviewedAt = $reviewed ? $now : null;
        $insert->execute([$athleteId, $guardianId, $typeId, 'athletes-seed/' . $filename, 'autorizacao-ficticia-' . $athleteId . '.pdf', filesize($absolute), date('Y-m-d', strtotime('+1 year')), $status, 'Documento ficticio do seed.', $status === 'rejected' ? 'Arquivo ficticio rejeitado para teste.' : null, $reviewed, $reviewedAt, $adminId, $now, $now]);
    }

    private static function idMap(PDO $pdo, string $table, string $column): array
    {
        $rows = $pdo->query('SELECT id, `' . $column . '` AS item_key FROM ' . $table)->fetchAll();
        $map = [];
        foreach ($rows as $row) $map[(string) $row['item_key']] = (int) $row['id'];
        return $map;
    }

    private static function userId(PDO $pdo, string $email): int
    {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $id = (int) $statement->fetchColumn();
        if (!$id) throw new \RuntimeException('Usuario necessario ao seed nao encontrado: ' . $email);
        return $id;
    }
}
