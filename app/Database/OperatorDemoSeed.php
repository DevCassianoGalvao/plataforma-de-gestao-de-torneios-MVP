<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

/** Creates one isolated, private championship for testing the match operator flow. */
final class OperatorDemoSeed
{
    public static function run(PDO $pdo, ?string $operatorEmail = null): array
    {
        $adminId = self::administratorId($pdo);
        if (!$adminId) {
            throw new \RuntimeException('Nenhum administrador ativo foi encontrado.');
        }

        $pdo->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $seasonId = self::seasonId($pdo, $now);
            $categoryId = self::categoryId($pdo, $now);
            $championshipId = self::championshipId($pdo, $seasonId, $categoryId, $adminId, $now);
            $teamA = self::teamId($pdo, $championshipId, 'Aurora de Demonstração', 'aurora-demonstracao', 'AUR', $adminId, $now);
            $teamB = self::teamId($pdo, $championshipId, 'Estrela de Demonstração', 'estrela-demonstracao', 'EST', $adminId, $now);
            $venueId = self::venueId($pdo, $championshipId, $adminId, $now);
            $phaseId = self::phaseId($pdo, $championshipId, $adminId, $now);
            $groupId = self::groupId($pdo, $phaseId, $now);
            self::groupTeam($pdo, $phaseId, $groupId, $teamA, $now);
            self::groupTeam($pdo, $phaseId, $groupId, $teamB, $now);
            $roundId = self::roundId($pdo, $phaseId, $groupId, $now);
            $matchId = self::matchId($pdo, $championshipId, $phaseId, $groupId, $roundId, $venueId, $teamA, $teamB, $adminId, $now);

            $operatorId = self::operatorId($pdo, $operatorEmail);
            $assignment = 'none';
            if ($operatorId) {
                $pdo->prepare("INSERT INTO match_operator_assignments (match_id, user_id, assignment_type, status, created_by, created_at) VALUES (?, ?, 'operator', 'active', ?, ?) ON DUPLICATE KEY UPDATE status = 'active', ended_at = NULL")
                    ->execute([$matchId, $operatorId, $adminId, $now]);
                $assignment = 'assigned:' . $operatorId;
            } elseif ($operatorEmail !== null && trim($operatorEmail) !== '') {
                $assignment = 'operator-not-found';
            } else {
                $count = (int) $pdo->query("SELECT COUNT(*) FROM users u INNER JOIN user_roles ur ON ur.user_id = u.id INNER JOIN roles r ON r.id = ur.role_id WHERE r.`key` = 'match_operator' AND u.status = 'active' AND u.deleted_at IS NULL")->fetchColumn();
                if ($count > 1) $assignment = 'choose-operator';
            }

            $pdo->commit();
            return compact('championshipId', 'teamA', 'teamB', 'matchId', 'assignment');
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private static function administratorId(PDO $pdo): int
    {
        return (int) $pdo->query("SELECT u.id FROM users u INNER JOIN user_roles ur ON ur.user_id = u.id INNER JOIN roles r ON r.id = ur.role_id WHERE r.`key` = 'administrator' AND u.status = 'active' AND u.deleted_at IS NULL ORDER BY u.id LIMIT 1")->fetchColumn();
    }

    private static function seasonId(PDO $pdo, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM seasons WHERE name = ? AND year = 2026 AND deleted_at IS NULL LIMIT 1');
        $find->execute(['Temporada de demonstração']);
        $id = (int) $find->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO seasons (name, year, status, created_at, updated_at) VALUES (?, 2026, \'active\', ?, ?)');
        $insert->execute(['Temporada de demonstração', $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function categoryId(PDO $pdo, string $now): int
    {
        $id = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'adulto-masculino' AND deleted_at IS NULL LIMIT 1")->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO categories (name, slug, description, minimum_age, maximum_age, gender_rule, status, created_at, updated_at) VALUES (?, ?, ?, 18, NULL, ?, \'active\', ?, ?)');
        $insert->execute(['Adulto Masculino', 'adulto-masculino', 'Categoria adulta para demonstração.', 'male', $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function championshipId(PDO $pdo, int $seasonId, int $categoryId, int $adminId, string $now): int
    {
        $slug = 'demonstracao-operador-2026';
        $find = $pdo->prepare('SELECT id, deleted_at FROM championships WHERE slug = ? LIMIT 1');
        $find->execute([$slug]);
        $row = $find->fetch();
        if ($row) {
            if ($row['deleted_at'] !== null) throw new \RuntimeException('O campeonato de demonstração está arquivado. Restaure-o antes de reutilizar o cenário.');
            return (int) $row['id'];
        }
        $fields = ['name', 'short_name', 'slug', 'description', 'season_id', 'category_id', 'status', 'visibility', 'default_theme', 'primary_color', 'secondary_color', 'accent_color', 'created_by', 'created_at', 'updated_at'];
        $values = ['Campeonato Demonstração do Operador 2026', 'Demonstração do Operador', $slug, 'Ambiente privado para testar o fluxo de operação de partidas.', $seasonId, $categoryId, 'draft', 'private', 'dark', '#123C32', '#245C4A', '#D9A441', $adminId, $now, $now];
        if (self::hasColumn($pdo, 'championships', 'requires_guardian')) {
            $fields[] = 'requires_guardian';
            $values[] = 0;
        }
        if (self::hasColumn($pdo, 'championships', 'allow_underage_athletes')) {
            $fields[] = 'allow_underage_athletes';
            $values[] = 0;
        }
        $insert = $pdo->prepare('INSERT INTO championships (' . implode(', ', $fields) . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')');
        $insert->execute($values);
        return (int) $pdo->lastInsertId();
    }

    private static function hasColumn(PDO $pdo, string $table, string $column): bool
    {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
        $statement->execute([$table, $column]);
        return (int) $statement->fetchColumn() > 0;
    }

    private static function teamId(PDO $pdo, int $championshipId, string $name, string $slug, string $abbr, int $adminId, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM teams WHERE championship_id = ? AND slug = ? AND deleted_at IS NULL LIMIT 1');
        $find->execute([$championshipId, $slug]);
        $id = (int) $find->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO teams (championship_id, name, short_name, slug, abbreviation, city, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, \'active\', ?, ?, ?)');
        $insert->execute([$championshipId, $name, $name, $slug, $abbr, 'Cidade de demonstração', $adminId, $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function venueId(PDO $pdo, int $championshipId, int $adminId, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM venues WHERE championship_id = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
        $find->execute([$championshipId, 'Campo de Demonstração']);
        $id = (int) $find->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO venues (championship_id, name, city, status, created_by, created_at, updated_at) VALUES (?, ?, ?, \'active\', ?, ?, ?)');
        $insert->execute([$championshipId, 'Campo de Demonstração', 'Cidade de demonstração', $adminId, $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function phaseId(PDO $pdo, int $championshipId, int $adminId, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM competition_phases WHERE championship_id = ? AND slug = ? LIMIT 1');
        $find->execute([$championshipId, 'fase-demonstracao']);
        $id = (int) $find->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO competition_phases (championship_id, name, slug, phase_type, sequence_number, group_count, teams_per_group, qualified_per_group, status, created_by, created_at, updated_at) VALUES (?, ?, ?, \'groups\', 1, 1, 2, 2, \'draft\', ?, ?, ?)');
        $insert->execute([$championshipId, 'Fase de demonstração', 'fase-demonstracao', $adminId, $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function groupId(PDO $pdo, int $phaseId, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM competition_groups WHERE phase_id = ? AND code = ? LIMIT 1');
        $find->execute([$phaseId, 'UNICO']);
        $id = (int) $find->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO competition_groups (phase_id, name, code, display_order, teams_limit, qualified_limit, status, created_at, updated_at) VALUES (?, ?, ?, 1, 2, 2, \'draft\', ?, ?)');
        $insert->execute([$phaseId, 'Grupo único', 'UNICO', $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function groupTeam(PDO $pdo, int $phaseId, int $groupId, int $teamId, string $now): void
    {
        $pdo->prepare('INSERT IGNORE INTO group_teams (phase_id, group_id, team_id, status, joined_at, updated_at) VALUES (?, ?, ?, \'active\', ?, ?)')->execute([$phaseId, $groupId, $teamId, $now, $now]);
    }

    private static function roundId(PDO $pdo, int $phaseId, int $groupId, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM competition_rounds WHERE group_id = ? AND round_number = 1 LIMIT 1');
        $find->execute([$groupId]);
        $id = (int) $find->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO competition_rounds (phase_id, group_id, round_number, period_start, period_end, status, created_at, updated_at) VALUES (?, ?, 1, \'2026-09-01\', \'2026-09-01\', \'draft\', ?, ?)');
        $insert->execute([$phaseId, $groupId, $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function matchId(PDO $pdo, int $championshipId, int $phaseId, int $groupId, int $roundId, int $venueId, int $teamA, int $teamB, int $adminId, string $now): int
    {
        $fixtureKey = hash('sha256', 'operator-demo|' . $championshipId . '|' . $teamA . '|' . $teamB);
        $find = $pdo->prepare('SELECT id FROM matches WHERE fixture_key = ? LIMIT 1');
        $find->execute([$fixtureKey]);
        $id = (int) $find->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO matches (championship_id, phase_id, group_id, round_id, home_team_id, away_team_id, venue_id, fixture_key, leg_number, match_order, match_date, match_time, status, observation, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1, \'2026-09-01\', \'18:00:00\', \'scheduled\', ?, ?, ?, ?)');
        $insert->execute([$championshipId, $phaseId, $groupId, $roundId, $teamA, $teamB, $venueId, $fixtureKey, 'Partida criada para demonstração do operador.', $adminId, $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function operatorId(PDO $pdo, ?string $email): int
    {
        if ($email !== null && trim($email) !== '') {
            $statement = $pdo->prepare("SELECT u.id FROM users u INNER JOIN user_roles ur ON ur.user_id = u.id INNER JOIN roles r ON r.id = ur.role_id WHERE u.email = ? AND r.`key` = 'match_operator' AND u.status = 'active' AND u.deleted_at IS NULL LIMIT 1");
            $statement->execute([trim($email)]);
            return (int) $statement->fetchColumn();
        }
        $rows = $pdo->query("SELECT DISTINCT u.id FROM users u INNER JOIN user_roles ur ON ur.user_id = u.id INNER JOIN roles r ON r.id = ur.role_id WHERE r.`key` = 'match_operator' AND u.status = 'active' AND u.deleted_at IS NULL ORDER BY u.id")->fetchAll(PDO::FETCH_COLUMN);
        return count($rows) === 1 ? (int) $rows[0] : 0;
    }
}
