<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class LineupSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        $championship = $pdo->query("SELECT id, category_id FROM championships WHERE slug = 'copa-brasil-de-talentos-2026' LIMIT 1")->fetch();
        $adminId = self::userId($pdo, 'admin@torneios.local');
        if (!$championship || !$adminId) throw new \RuntimeException('Dados necessarios ao seed de escalacoes nao foram encontrados.');
        $positions = self::positionMap($pdo);
        $now = date('Y-m-d H:i:s');
        $positionCodes = ['goalkeeper', 'center_back', 'center_back', 'right_back', 'left_back', 'defensive_midfielder', 'central_midfielder', 'central_midfielder', 'attacking_midfielder', 'right_winger', 'left_winger', 'center_forward'];
        $teams = $pdo->prepare("SELECT id, short_name FROM teams WHERE championship_id = ? AND deleted_at IS NULL AND status = 'active' ORDER BY id LIMIT 10");
        $teams->execute([(int) $championship['id']]);
        foreach ($teams->fetchAll() as $team) {
            for ($slot = 3; $slot <= 14; $slot++) {
                $name = 'Escalacao ' . $team['short_name'] . ' ' . $slot;
                $find = $pdo->prepare('SELECT id FROM athletes WHERE team_id = ? AND full_name = ? AND deleted_at IS NULL LIMIT 1');
                $find->execute([(int) $team['id'], $name]);
                $athleteId = (int) $find->fetchColumn();
                $code = $positionCodes[$slot - 3];
                if (!$athleteId) {
                    $insert = $pdo->prepare("INSERT INTO athletes (team_id, full_name, sporting_name, birth_date, gender, primary_position_id, preferred_number, dominant_foot, status, private_notes, created_by, created_at, updated_at) VALUES (?, ?, ?, '2012-06-15', 'male', ?, ?, 'right', 'active', 'Atleta ficticio adicional para testes de escalacao.', ?, ?, ?)");
                    $insert->execute([(int) $team['id'], $name, 'Esc ' . $team['short_name'] . ' ' . $slot, $positions[$code], $slot, $adminId, $now, $now]);
                    $athleteId = (int) $pdo->lastInsertId();
                }
                $secondary = $positionCodes[($slot - 2) % count($positionCodes)];
                $pdo->prepare('INSERT IGNORE INTO athlete_secondary_positions (athlete_id, position_id, created_at) VALUES (?, ?, ?)')->execute([$athleteId, $positions[$secondary], $now]);
                $registration = $pdo->prepare('SELECT id FROM athlete_registrations WHERE championship_id = ? AND team_id = ? AND athlete_id = ? LIMIT 1');
                $registration->execute([(int) $championship['id'], (int) $team['id'], $athleteId]);
                if (!$registration->fetchColumn()) {
                    $insertRegistration = $pdo->prepare("INSERT INTO athlete_registrations (championship_id, team_id, athlete_id, category_id, requested_number, status, submitted_at, reviewed_by, reviewed_at, decided_at, observations, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, ?, ?, 'Registro aprovado ficticio para Etapa 8.', ?, ?, ?, ?)");
                    $insertRegistration->execute([(int) $championship['id'], (int) $team['id'], $athleteId, (int) $championship['category_id'], $slot, $now, $adminId, $now, $now, $adminId, $adminId, $now, $now]);
                    $registrationId = (int) $pdo->lastInsertId();
                    $history = $pdo->prepare("INSERT INTO athlete_registration_history (registration_id, from_status, to_status, action, notes, user_id, created_at) VALUES (?, NULL, 'draft', 'created', NULL, ?, ?), (?, 'draft', 'approved', 'approved', 'Seed da Etapa 8.', ?, ?)");
                    $history->execute([$registrationId, $adminId, $now, $registrationId, $adminId, $now]);
                }
            }
        }
    }

    private static function positionMap(PDO $pdo): array
    {
        $rows = $pdo->query('SELECT id, `code` FROM positions')->fetchAll();
        $map = [];
        foreach ($rows as $row) $map[(string) $row['code']] = (int) $row['id'];
        return $map;
    }

    private static function userId(PDO $pdo, string $email): int
    {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        return (int) $statement->fetchColumn();
    }
}
