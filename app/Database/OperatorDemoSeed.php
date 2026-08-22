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

            $athleteCount = self::athletesAndRegistrations($pdo, $championshipId, $categoryId, $teamA, $teamB, $adminId, $now);
            $lineupCount = self::confirmedLineups($pdo, $matchId, $championshipId, $teamA, $teamB, $adminId, $now);

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
            return compact('championshipId', 'teamA', 'teamB', 'matchId', 'assignment', 'athleteCount', 'lineupCount');
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

    private static function athletesAndRegistrations(PDO $pdo, int $championshipId, int $categoryId, int $teamA, int $teamB, int $adminId, string $now): int
    {
        $positionIds = [];
        $positions = $pdo->query('SELECT id, code FROM positions WHERE status = \'active\'')->fetchAll();
        foreach ($positions as $position) $positionIds[(string) $position['code']] = (int) $position['id'];
        foreach (['goalkeeper', 'center_back', 'right_back', 'left_back', 'defensive_midfielder', 'central_midfielder', 'attacking_midfielder', 'right_winger', 'left_winger', 'center_forward'] as $code) {
            if (!isset($positionIds[$code])) throw new \RuntimeException('Catálogo de posições incompleto para o cenário demo: ' . $code . '.');
        }

        $roster = [
            ['goalkeeper', 1], ['center_back', 2], ['right_back', 3], ['left_back', 4],
            ['center_back', 5], ['defensive_midfielder', 6], ['central_midfielder', 7],
            ['attacking_midfielder', 8], ['right_winger', 9], ['left_winger', 10],
            ['center_forward', 11], ['goalkeeper', 12], ['center_back', 13], ['central_midfielder', 14],
        ];
        $teams = [[$teamA, 'Aurora'], [$teamB, 'Estrela']];
        $created = 0;
        $findAthlete = $pdo->prepare('SELECT id FROM athletes WHERE team_id = ? AND full_name = ? AND deleted_at IS NULL LIMIT 1');
        $insertAthlete = $pdo->prepare('INSERT INTO athletes (team_id, full_name, sporting_name, birth_date, gender, primary_position_id, preferred_number, dominant_foot, status, private_notes, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, \'male\', ?, ?, \'right\', \'active\', ?, ?, ?, ?)');
        $updateAthlete = $pdo->prepare('UPDATE athletes SET sporting_name = ?, birth_date = ?, gender = \'male\', primary_position_id = ?, preferred_number = ?, dominant_foot = \'right\', status = \'active\', deleted_at = NULL, updated_at = ? WHERE id = ?');
        $findRegistration = $pdo->prepare('SELECT id, status FROM athlete_registrations WHERE championship_id = ? AND team_id = ? AND athlete_id = ? LIMIT 1');
        $insertRegistration = $pdo->prepare("INSERT INTO athlete_registrations (championship_id, team_id, athlete_id, category_id, requested_number, status, submitted_at, reviewed_by, reviewed_at, decided_at, observations, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $updateRegistration = $pdo->prepare("UPDATE athlete_registrations SET category_id = ?, requested_number = ?, status = 'approved', submitted_at = COALESCE(submitted_at, ?), reviewed_by = ?, reviewed_at = COALESCE(reviewed_at, ?), decided_at = COALESCE(decided_at, ?), updated_by = ?, updated_at = ? WHERE id = ?");
        $insertHistory = $pdo->prepare("INSERT INTO athlete_registration_history (registration_id, from_status, to_status, action, notes, user_id, created_at) VALUES (?, ?, 'approved', ?, ?, ?, ?)");
        $insertSecondary = $pdo->prepare('INSERT IGNORE INTO athlete_secondary_positions (athlete_id, position_id, created_at) VALUES (?, ?, ?)');

        foreach ($teams as [$teamId, $prefix]) {
            foreach ($roster as $index => [$positionCode, $number]) {
                $name = $prefix . ' Demo ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                $findAthlete->execute([$teamId, $name]);
                $athleteId = (int) $findAthlete->fetchColumn();
                $birthDate = sprintf('2000-%02d-%02d', (($index % 9) + 1), (($index % 25) + 1));
                if (!$athleteId) {
                    $insertAthlete->execute([$teamId, $name, $name, $birthDate, $positionIds[$positionCode], $number, 'Atleta fictício criado para testar a operação da partida.', $adminId, $now, $now]);
                    $athleteId = (int) $pdo->lastInsertId();
                    $created++;
                } else {
                    $updateAthlete->execute([$name, $birthDate, $positionIds[$positionCode], $number, $now, $athleteId]);
                }
                $secondaryCode = $positionCode === 'center_forward' ? 'attacking_midfielder' : ($positionCode === 'central_midfielder' ? 'defensive_midfielder' : null);
                if ($secondaryCode !== null) $insertSecondary->execute([$athleteId, $positionIds[$secondaryCode], $now]);

                $findRegistration->execute([$championshipId, $teamId, $athleteId]);
                $registration = $findRegistration->fetch();
                if (!$registration) {
                    $insertRegistration->execute([$championshipId, $teamId, $athleteId, $categoryId, $number, $now, $adminId, $now, $now, 'Registro demo aprovado para testar eventos.', $adminId, $adminId, $now, $now]);
                    $registrationId = (int) $pdo->lastInsertId();
                    $insertHistory->execute([$registrationId, 'draft', 'created', 'Cenário demo criado com inscrição aprovada.', $adminId, $now]);
                    $insertHistory->execute([$registrationId, 'submitted', 'approved', 'Inscrição demo aprovada automaticamente para teste.', $adminId, $now]);
                } else {
                    $registrationId = (int) $registration['id'];
                    $previousStatus = (string) $registration['status'];
                    $updateRegistration->execute([$categoryId, $number, $now, $adminId, $now, $now, $adminId, $now, $registrationId]);
                    if ($previousStatus !== 'approved') $insertHistory->execute([$registrationId, $previousStatus, 'approved', 'Inscrição demo restaurada como aprovada.', $adminId, $now]);
                }
            }
        }
        return 28;
    }

    private static function confirmedLineups(PDO $pdo, int $matchId, int $championshipId, int $teamA, int $teamB, int $adminId, string $now): int
    {
        $formationId = (int) $pdo->query("SELECT id FROM tactical_formations WHERE slug = '4-3-3' AND active = 1 LIMIT 1")->fetchColumn();
        if (!$formationId) throw new \RuntimeException('Formação 4-3-3 não encontrada. Execute o seed base antes do cenário demo.');
        $slotsStatement = $pdo->prepare('SELECT slot_key, position_code, position_group, display_order FROM tactical_formation_slots WHERE tactical_formation_id = ? ORDER BY display_order, id');
        $slotsStatement->execute([$formationId]);
        $slots = $slotsStatement->fetchAll();
        if (count($slots) !== 11) throw new \RuntimeException('A formação 4-3-3 precisa ter onze posições configuradas.');
        $findLineup = $pdo->prepare('SELECT id FROM match_lineups WHERE match_id = ? AND team_id = ? LIMIT 1');
        $athletesStatement = $pdo->prepare("SELECT a.id, a.preferred_number, p.code AS position_code, p.position_group FROM athlete_registrations ar INNER JOIN athletes a ON a.id = ar.athlete_id INNER JOIN positions p ON p.id = a.primary_position_id WHERE ar.championship_id = ? AND ar.team_id = ? AND ar.status = 'approved' AND a.team_id = ? AND a.status = 'active' AND a.deleted_at IS NULL ORDER BY a.preferred_number, a.id");
        $insertLineup = $pdo->prepare("INSERT INTO match_lineups (match_id, team_id, tactical_formation_id, status, version, captain_athlete_id, goalkeeper_athlete_id, confirmed_by, confirmed_at, created_by, created_at, updated_at) VALUES (?, ?, ?, 'confirmed', 1, ?, ?, ?, ?, ?, ?, ?)");
        $insertPlayer = $pdo->prepare('INSERT INTO match_lineup_players (lineup_id, athlete_id, role, slot_key, position_code, shirt_number, is_captain, is_goalkeeper, is_out_of_position, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertHistory = $pdo->prepare("INSERT INTO match_lineup_history (lineup_id, action, version, status, tactical_formation_id, reason, changed_by, created_at) VALUES (?, 'seeded', 1, 'confirmed', ?, 'Escalação fictícia criada para teste do operador.', ?, ?)");
        $created = 0;
        foreach ([$teamA, $teamB] as $teamId) {
            $findLineup->execute([$matchId, $teamId]);
            if ($findLineup->fetchColumn()) continue;
            $athletesStatement->execute([$championshipId, $teamId, $teamId]);
            $available = $athletesStatement->fetchAll();
            if (count($available) < 11) throw new \RuntimeException('A equipe demo não possui onze atletas aprovados.');
            $chosen = [];
            foreach ($slots as $slot) {
                $best = 0; $score = -1;
                foreach ($available as $index => $athlete) {
                    $candidateScore = $athlete['position_code'] === $slot['position_code'] ? 100 : ($athlete['position_group'] === $slot['position_group'] ? 60 : 0);
                    if ($candidateScore > $score) { $score = $candidateScore; $best = $index; }
                }
                $athlete = $available[$best]; array_splice($available, $best, 1);
                $chosen[] = [$slot, $athlete, $score < 100];
            }
            $goalkeeper = (int) $chosen[0][1]['id']; $captain = (int) $chosen[1][1]['id'];
            $insertLineup->execute([$matchId, $teamId, $formationId, $captain, $goalkeeper, $adminId, $now, $adminId, $now, $now]);
            $lineupId = (int) $pdo->lastInsertId();
            foreach ($chosen as $order => [$slot, $athlete, $outOfPosition]) {
                $insertPlayer->execute([$lineupId, (int) $athlete['id'], 'starter', $slot['slot_key'], $slot['position_code'], (int) $athlete['preferred_number'], (int) $athlete['id'] === $captain ? 1 : 0, (int) $athlete['id'] === $goalkeeper ? 1 : 0, $outOfPosition ? 1 : 0, $order + 1, $now, $now]);
            }
            foreach (array_slice($available, 0, 3) as $order => $athlete) {
                $insertPlayer->execute([$lineupId, (int) $athlete['id'], 'reserve', null, $athlete['position_code'], (int) $athlete['preferred_number'], 0, 0, 0, $order + 1, $now, $now]);
            }
            $insertHistory->execute([$lineupId, $formationId, $adminId, $now]);
            $created++;
        }
        return $created;
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
