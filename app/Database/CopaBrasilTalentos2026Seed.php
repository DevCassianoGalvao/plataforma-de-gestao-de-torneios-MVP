<?php
declare(strict_types=1);

namespace App\Database;

use App\Services\StorageService;
use PDO;

/** Configuracao idempotente do campeonato real a partir do regulamento enviado. */
final class CopaBrasilTalentos2026Seed
{
    public static function run(PDO $pdo, ?string $trainerPassword = null): array
    {
        if (getenv('APP_ENV') === 'production' && getenv('ALLOW_COPA_BRASIL_SEED') !== '1') {
            throw new \RuntimeException('Seed bloqueado em producao. Defina ALLOW_COPA_BRASIL_SEED=1 para confirmar a configuracao inicial.');
        }

        $now = date('Y-m-d H:i:s');
        $adminId = self::userId($pdo, 'admin@torneios.local') ?: self::activeAdministratorId($pdo);
        if (!$adminId) {
            throw new \RuntimeException('O usuario administrador do seed nao foi encontrado.');
        }

        $pdo->beginTransaction();
        try {
            $seasonId = self::season($pdo, $now);
            $categoryId = self::category($pdo, $now);
            $championshipId = self::championship($pdo, $seasonId, $categoryId, $adminId, $now);
            $regulationId = self::regulation($pdo, $championshipId, $adminId, $now);
            self::assets($pdo, $championshipId, $regulationId, $now);
            self::evidenceChecklist($pdo, $championshipId, $adminId, $now);
            self::regulationSettings($pdo, $regulationId, $now);
            $phases = self::phases($pdo, $championshipId, $adminId, $now);
            $teams = self::teams($pdo, $championshipId, $adminId, $now);
            $trainerAccounts = self::coaches($pdo, $championshipId, $teams, $adminId, $now, $trainerPassword);
            self::groups($pdo, $phases['groups'], $teams, $now);
            self::eligibility($pdo, $regulationId, $phases['groups'], $phases['knockout'], $now);
            self::assignment($pdo, $championshipId, $adminId, $now);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return ['championship_id' => $championshipId, 'regulation_id' => $regulationId, 'team_ids' => $teams, 'phase_ids' => $phases, 'trainer_accounts' => $trainerAccounts];
    }

    private static function season(PDO $pdo, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM seasons WHERE name = ? AND year = ? AND deleted_at IS NULL LIMIT 1');
        $find->execute(['Temporada 2026', 2026]);
        $id = (int) $find->fetchColumn();
        if ($id) return $id;
        $insert = $pdo->prepare('INSERT INTO seasons (name, year, starts_at, ends_at, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $insert->execute(['Temporada 2026', 2026, '2026-01-01', '2026-12-31', 'active', $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function category(PDO $pdo, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM categories WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
        $find->execute(['adulto-masculino']);
        $id = (int) $find->fetchColumn();
        if ($id) {
            $pdo->prepare('UPDATE categories SET name = ?, minimum_age = ?, maximum_age = NULL, gender_rule = ?, status = ?, updated_at = ? WHERE id = ?')->execute(['Adulto Masculino', 18, 'male', 'active', $now, $id]);
            return $id;
        }
        $insert = $pdo->prepare('INSERT INTO categories (name, slug, description, minimum_age, maximum_age, gender_rule, status, created_at, updated_at) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?)');
        $insert->execute(['Adulto Masculino', 'adulto-masculino', 'Categoria adulta da Copa Brasil de Talentos 2026.', 18, 'male', 'active', $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function championship(PDO $pdo, int $seasonId, int $categoryId, int $adminId, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM championships WHERE slug = ? LIMIT 1');
        $find->execute(['copa-brasil-de-talentos-2026']);
        $id = (int) $find->fetchColumn();
        $values = ['Copa Brasil de Talentos 2026', 'Copa Brasil de Talentos', 'copa-brasil-de-talentos-2026', 'Competicao de futebol com dez equipes, duas fases de grupos e mata-mata conforme o regulamento oficial enviado a organizacao.', $seasonId, $categoryId, 'configured', 'public', 'dark', '#0A49DB', '#001B67', '#D9A441'];
        if ($id) {
            $pdo->prepare('UPDATE championships SET name = ?, short_name = ?, description = ?, season_id = ?, category_id = ?, starts_at = NULL, ends_at = NULL, registration_starts_at = NULL, registration_ends_at = NULL, status = ?, visibility = ?, default_theme = ?, primary_color = ?, secondary_color = ?, accent_color = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL')->execute([$values[0], $values[1], $values[3], $seasonId, $categoryId, $values[6], $values[7], $values[8], $values[9], $values[10], $values[11], $now, $id]);
            return $id;
        }
        $insert = $pdo->prepare('INSERT INTO championships (name, short_name, slug, description, season_id, category_id, starts_at, ends_at, registration_starts_at, registration_ends_at, status, visibility, default_theme, primary_color, secondary_color, accent_color, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([$values[0], $values[1], $values[2], $values[3], $seasonId, $categoryId, $values[6], $values[7], $values[8], $values[9], $values[10], $values[11], $adminId, $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function regulation(PDO $pdo, int $championshipId, int $adminId, string $now): int
    {
        $find = $pdo->prepare('SELECT id FROM regulations WHERE championship_id = ? AND version_number = 1 LIMIT 1');
        $find->execute([$championshipId]);
        $id = (int) $find->fetchColumn();
        if ($id) {
            $pdo->prepare('UPDATE regulations SET name = ?, status = ?, published_at = ?, updated_at = ? WHERE id = ?')->execute(['Regulamento oficial - Copa Brasil de Talentos 2026', 'published', $now, $now, $id]);
            return $id;
        }
        $insert = $pdo->prepare('INSERT INTO regulations (championship_id, version_number, name, status, effective_from, published_at, created_by, created_at, updated_at) VALUES (?, 1, ?, ?, NULL, ?, ?, ?, ?)');
        $insert->execute([$championshipId, 'Regulamento oficial - Copa Brasil de Talentos 2026', 'published', $now, $adminId, $now, $now]);
        return (int) $pdo->lastInsertId();
    }

    private static function regulationSettings(PDO $pdo, int $regulationId, string $now): void
    {
        self::upsert($pdo, 'regulation_format_settings', $regulationId, [
            'group_count' => 2, 'teams_per_group' => 5, 'qualified_per_group' => 2, 'group_rounds' => 'single', 'home_and_away' => 0,
            'knockout_starts_at' => 'semifinals', 'third_place_match' => 0, 'final_format' => 'two_legs',
        ], $now);
        self::upsert($pdo, 'regulation_points_settings', $regulationId, ['points_win' => 3, 'points_draw' => 1, 'points_loss' => 0, 'wo_winner_goals' => 3, 'wo_loser_goals' => 0], $now);
        self::upsert($pdo, 'regulation_discipline_settings', $regulationId, [
            'yellow_cards_for_suspension' => 3, 'yellow_suspension_matches' => 1, 'red_card_automatic_suspension' => 1,
            'red_card_suspension_matches' => 1, 'reset_cards_enabled' => 1, 'reset_cards_stage' => 'semifinals',
        ], $now);
        self::upsert($pdo, 'regulation_match_settings', $regulationId, [
            'regular_time_minutes' => 90, 'halftime_minutes' => 15, 'substitutions_allowed' => 7, 'substitution_windows' => 3,
            'extra_time_enabled' => 0, 'extra_time_minutes' => 0, 'penalty_shootout_enabled' => 1, 'direct_penalties' => 0,
        ], $now);
        self::upsert($pdo, 'regulation_roster_settings', $regulationId, ['minimum_roster_size' => 22, 'maximum_roster_size' => 22, 'minimum_goalkeepers' => 1, 'allow_multiple_team_registration' => 0], $now);
        self::upsert($pdo, 'regulation_advanced_settings', $regulationId, [
            'maximum_staff_members' => 3, 'maximum_teams' => 10, 'allow_registration_after_start' => 0, 'registration_requires_approval' => 1,
            'require_complete_documents' => 1, 'require_minor_authorization' => 0, 'roster_change_limit' => 5, 'roster_change_deadline' => null,
            'roster_change_phase_limit' => null, 'transfers_enabled' => 1, 'transfers_blocked' => 0, 'block_athlete_played_other_team' => 1,
            'allow_administrative_exception' => 0, 'exception_reason_required' => 1, 'abandoned_match_rule' => 'administrative_decision',
            'cancelled_match_rule' => 'administrative_decision', 'postponed_match_rule' => 'reschedule',
        ], $now);
        self::upsert($pdo, 'regulation_competition_rules', $regulationId, [
            'non_local_athlete_limit' => 8, 'registration_deadline_days_before_start' => 10, 'roster_replacement_notice_days' => 5,
            'wo_min_players' => 7, 'wo_tolerance_minutes' => 15, 'wo_counts_for_wins' => 1, 'wo_counts_for_goal_difference' => 0,
            'wo_counts_for_goals' => 0, 'wo_eliminates_team' => 1, 'bench_athlete_limit' => 11, 'required_first_phase_participation' => 1,
            'fixed_shirt_number' => 1, 'suspended_next_edition' => 1,
        ], $now);

        $pdo->prepare('DELETE FROM regulation_tiebreakers WHERE regulation_id = ?')->execute([$regulationId]);
        $criterion = $pdo->prepare('INSERT INTO regulation_tiebreakers (regulation_id, criterion, priority, enabled, created_at) VALUES (?, ?, ?, 1, ?)');
        foreach (['head_to_head', 'wins', 'goal_difference', 'goals_conceded', 'fewer_cards', 'draw_lots'] as $priority => $name) $criterion->execute([$regulationId, $name, $priority + 1, $now]);
        $pdo->prepare('DELETE FROM regulation_knockout_pairings WHERE regulation_id = ?')->execute([$regulationId]);
        $pairing = $pdo->prepare('INSERT INTO regulation_knockout_pairings (regulation_id, stage, tie_number, home_source, away_source, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        foreach ([['semifinals', 1, 'A1', 'B2'], ['semifinals', 2, 'B1', 'A2'], ['final', 1, 'SF1', 'SF2']] as $item) $pairing->execute([$regulationId, $item[0], $item[1], $item[2], $item[3], $now, $now]);
        $docType = (int) $pdo->query("SELECT id FROM athlete_document_types WHERE `key` = 'athlete_document' LIMIT 1")->fetchColumn();
        if ($docType) {
            $pdo->prepare('DELETE FROM regulation_required_documents WHERE regulation_id = ?')->execute([$regulationId]);
            $pdo->prepare('INSERT INTO regulation_required_documents (regulation_id, document_type_id, required_for_minor, display_order, created_at) VALUES (?, ?, 0, 1, ?)')->execute([$regulationId, $docType, $now]);
        }
    }

    private static function phases(PDO $pdo, int $championshipId, int $adminId, string $now): array
    {
        $phase = $pdo->prepare('INSERT INTO competition_phases (championship_id, name, slug, phase_type, sequence_number, group_count, teams_per_group, qualified_per_group, status, published_at, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), phase_type = VALUES(phase_type), sequence_number = VALUES(sequence_number), group_count = VALUES(group_count), teams_per_group = VALUES(teams_per_group), qualified_per_group = VALUES(qualified_per_group), status = VALUES(status), published_at = COALESCE(published_at, VALUES(published_at)), updated_at = VALUES(updated_at)');
        $phase->execute([$championshipId, 'Fase de grupos', 'fase-grupos', 'groups', 1, 2, 5, 2, 'published', $now, $adminId, $now, $now]);
        $phase->execute([$championshipId, 'Mata-mata', 'mata-mata', 'knockout', 2, 1, 4, 0, 'published', $now, $adminId, $now, $now]);
        $find = $pdo->prepare('SELECT id, slug FROM competition_phases WHERE championship_id = ? AND slug IN (?, ?) ORDER BY sequence_number');
        $find->execute([$championshipId, 'fase-grupos', 'mata-mata']);
        $result = ['groups' => 0, 'knockout' => 0];
        foreach ($find->fetchAll() as $item) $result[$item['slug'] === 'fase-grupos' ? 'groups' : 'knockout'] = (int) $item['id'];
        $group = $pdo->prepare('INSERT INTO competition_groups (phase_id, name, code, display_order, teams_limit, qualified_limit, status, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, 5, 2, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), teams_limit = 5, qualified_limit = 2, status = VALUES(status), published_at = COALESCE(published_at, VALUES(published_at)), updated_at = VALUES(updated_at)');
        $group->execute([$result['groups'], 'Grupo A', 'A', 1, 'published', $now, $now, $now]);
        $group->execute([$result['groups'], 'Grupo B', 'B', 2, 'published', $now, $now, $now]);
        $group->execute([$result['knockout'], 'Chave principal', 'KO', 1, 'published', $now, $now, $now]);
        return $result;
    }

    private static function teams(PDO $pdo, int $championshipId, int $adminId, string $now): array
    {
        $root = dirname(__DIR__, 2) . '/COPA BRASIL DE TALENTOS 2026/Logos times';
        $items = [
            ['Boa Esperança FC', 'Boa Esperança', 'boa-esperanca-fc', 'BEF', 'WhatsApp Image 2026-08-07 at 17.51.51.jpeg', 'Gustavo', 'gustavoboy056@gmail.com'],
            ['Mury FC', 'Mury', 'mury-fc', 'MUR', 'WhatsApp Image 2026-08-07 at 17.51.51 (1).jpeg', null, 'gabrielemiterio@gmail.com'],
            ['Viguinha FC', 'Viguinha', 'viguinha-fc', 'VIG', 'WhatsApp Image 2026-08-07 at 17.51.51 (2).jpeg', null, 'dgouveiaknupp@gmail.com'],
            ['Sana FC', 'Sana', 'sana-fc', 'SAN', 'WhatsApp Image 2026-08-07 at 17.51.51 (3).jpeg', null, 'adilsonsilvacoelho1@gmail.com'],
            ['Lumiar FC', 'Lumiar', 'lumiar-fc', 'LUM', 'WhatsApp Image 2026-08-07 at 17.51.51 (4).jpeg', 'Wagner', 'wagner_heiderich@hotmail.com'],
            ['Santiago FC', 'Santiago', 'santiago-fc', 'SAN2', 'WhatsApp Image 2026-08-07 at 17.51.52.jpeg', 'Igor', 'igorfernandes1803@gmail.com'],
            ['Retiro Saudoso FC', 'Retiro Saudoso', 'retiro-saudoso-fc', 'RSF', 'WhatsApp Image 2026-08-07 at 17.51.52 (1).jpeg', 'Fernando', 'fernandosandta@gmail.com'],
            ['Bragantino FC', 'Bragantino', 'bragantino-fc', 'BRA', 'WhatsApp Image 2026-08-07 at 17.51.52 (2).jpeg', null, 'schuab94@gmail.com'],
            ['Ousadia e Alegria FC', 'Ousadia e Alegria', 'ousadia-e-alegria-fc', 'OEA', 'WhatsApp Image 2026-08-07 at 17.52.14.jpeg', null, 'luiiz9422@gmail.com'],
            ['Rio Bonito FC', 'Rio Bonito', 'rio-bonito-fc', 'RBF', 'WhatsApp Image 2026-08-07 at 17.52.14 (1).jpeg', 'Eduardo', 'Schenkelfabio@gmail.com'],
        ];
        $pdo->prepare('INSERT INTO staff_roles (`key`, name, description, active, display_order, created_at, updated_at) VALUES (?, ?, ?, 1, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), active = 1, updated_at = VALUES(updated_at)')->execute(['head_coach', 'Treinador', 'Treinador principal da equipe.', 10, $now, $now]);
        $role = (int) $pdo->query("SELECT id FROM staff_roles WHERE `key` = 'head_coach' LIMIT 1")->fetchColumn();
        if (!$role) throw new \RuntimeException('Cargo de treinador nao encontrado. Execute o seed base antes.');
        $result = [];
        foreach ($items as $item) {
            $find = $pdo->prepare('SELECT id, shield_path FROM teams WHERE championship_id = ? AND slug = ? LIMIT 1');
            $find->execute([$championshipId, $item[2]]);
            $existing = $find->fetch() ?: null;
            if ($existing) {
                $id = (int) $existing['id'];
                $pdo->prepare('UPDATE teams SET name = ?, short_name = ?, abbreviation = ?, status = ?, updated_at = ? WHERE id = ?')->execute([$item[0], $item[1], $item[3], 'active', $now, $id]);
            } else {
                $insert = $pdo->prepare('INSERT INTO teams (championship_id, name, short_name, slug, abbreviation, description, city, state, primary_color, secondary_color, shield_path, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, NULL, ?, ?, ?, ?)');
                $insert->execute([$championshipId, $item[0], $item[1], $item[2], $item[3], 'Equipe participante da Copa Brasil de Talentos 2026.', '#0A49DB', '#D9A441', 'active', $adminId, $now, $now]);
                $id = (int) $pdo->lastInsertId();
            }
            $current = (string) ($existing['shield_path'] ?? '');
            if ($current === '') {
                $source = $root . DIRECTORY_SEPARATOR . $item[4];
                if (!is_file($source)) throw new \RuntimeException('Escudo nao encontrado: ' . $item[4]);
                $stored = (new StorageService())->store(['error' => UPLOAD_ERR_OK, 'size' => filesize($source), 'tmp_name' => $source, 'name' => basename($source)], 'teams/' . $id, ['image/jpeg', 'image/png', 'image/webp'], 12582912);
                $pdo->prepare('UPDATE teams SET shield_path = ?, updated_at = ? WHERE id = ?')->execute([$stored['path'], $now, $id]);
            }
            if ($item[5] !== null) {
                $staff = $pdo->prepare('INSERT INTO team_staff (team_id, staff_role_id, user_id, full_name, display_name, email, status, starts_at, created_at, updated_at) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE email = VALUES(email), status = VALUES(status), updated_at = VALUES(updated_at)');
                $staff->execute([$id, $role, $item[5], $item[5], $item[6], 'active', date('Y-m-d'), $now, $now]);
            }
            $result[] = $id;
        }
        return $result;
    }

    private static function coaches(PDO $pdo, int $championshipId, array $teamIds, int $adminId, string $now, ?string $trainerPassword): array
    {
        $roleId = (int) $pdo->query("SELECT id FROM roles WHERE `key` = 'team_manager' LIMIT 1")->fetchColumn();
        if (!$roleId) throw new \RuntimeException('Perfil de treinador nao encontrado. Execute o seed de autenticacao antes.');

        $accounts = [
            ['boa-esperanca-fc', 'Gustavo', 'gustavoboy056@gmail.com'],
            ['mury-fc', null, 'gabrielemiterio@gmail.com'],
            ['viguinha-fc', null, 'dgouveiaknupp@gmail.com'],
            ['sana-fc', null, 'adilsonsilvacoelho1@gmail.com'],
            ['lumiar-fc', 'Wagner', 'wagner_heiderich@hotmail.com'],
            ['santiago-fc', 'Igor', 'igorfernandes1803@gmail.com'],
            ['retiro-saudoso-fc', 'Fernando', 'fernandosandta@gmail.com'],
            ['bragantino-fc', null, 'schuab94@gmail.com'],
            ['ousadia-e-alegria-fc', null, 'luiiz9422@gmail.com'],
            ['rio-bonito-fc', 'Eduardo', 'Schenkelfabio@gmail.com'],
        ];
        if (count($teamIds) !== count($accounts)) throw new \RuntimeException('A Copa precisa ter dez equipes antes de criar os acessos dos treinadores.');

        $staffRoleId = (int) $pdo->query("SELECT id FROM staff_roles WHERE `key` = 'head_coach' LIMIT 1")->fetchColumn();
        if (!$staffRoleId) throw new \RuntimeException('Cargo de treinador nao encontrado. Execute o seed base antes.');

        $findTeam = $pdo->prepare('SELECT id, short_name FROM teams WHERE championship_id = ? AND slug = ? AND deleted_at IS NULL LIMIT 1');
        $findUser = $pdo->prepare('SELECT id, status, deleted_at FROM users WHERE email = ? LIMIT 1');
        $createUser = $pdo->prepare("INSERT INTO users (name, email, password_hash, status, created_at, updated_at) VALUES (?, ?, ?, 'active', ?, ?)");
        $assignRole = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id, created_at, created_by) VALUES (?, ?, ?, ?)');
        $findAssignment = $pdo->prepare("SELECT id, team_id FROM team_user_assignments WHERE user_id = ? AND assignment_type = 'head_coach' AND status = 'active' LIMIT 1");
        $updateAssignment = $pdo->prepare("UPDATE team_user_assignments SET status = 'active', ends_at = NULL, updated_at = ? WHERE id = ?");
        $createAssignment = $pdo->prepare("INSERT INTO team_user_assignments (team_id, user_id, assignment_type, status, starts_at, created_by, created_at, updated_at) VALUES (?, ?, 'head_coach', 'active', ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = 'active', ends_at = NULL, updated_at = VALUES(updated_at)");
        $findStaff = $pdo->prepare('SELECT id FROM team_staff WHERE team_id = ? AND full_name = ? AND deleted_at IS NULL LIMIT 1');
        $updateStaff = $pdo->prepare("UPDATE team_staff SET user_id = ?, email = ?, status = 'active', updated_at = ? WHERE id = ?");
        $createStaff = $pdo->prepare("INSERT INTO team_staff (team_id, staff_role_id, user_id, full_name, display_name, email, status, starts_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)");

        $created = 0;
        $linked = 0;
        foreach ($accounts as [$slug, $knownName, $rawEmail]) {
            $findTeam->execute([$championshipId, $slug]);
            $team = $findTeam->fetch() ?: null;
            if (!$team) throw new \RuntimeException('Equipe do treinador nao encontrada: ' . $slug);
            $teamId = (int) $team['id'];
            $email = strtolower(trim($rawEmail));
            $displayName = $knownName ?: 'Treinador - ' . $team['short_name'];

            $findUser->execute([$email]);
            $user = $findUser->fetch() ?: null;
            if ($user && $user['deleted_at'] !== null) throw new \RuntimeException('O e-mail do treinador pertence a um usuario excluido: ' . $email);
            if (!$user) {
                if ($trainerPassword === null || strlen($trainerPassword) < 8 || !preg_match('/[A-Za-z]/', $trainerPassword) || !preg_match('/\d/', $trainerPassword)) {
                    throw new \RuntimeException('Defina COPA_TRAINER_INITIAL_PASSWORD com pelo menos 8 caracteres, uma letra e um numero para criar os acessos dos treinadores.');
                }
                $createUser->execute([$displayName, $email, password_hash($trainerPassword, PASSWORD_DEFAULT), $now, $now]);
                $userId = (int) $pdo->lastInsertId();
                $created++;
            } else {
                $userId = (int) $user['id'];
            }

            $assignRole->execute([$userId, $roleId, $now, $adminId]);
            $findAssignment->execute([$userId]);
            $assignment = $findAssignment->fetch() ?: null;
            if ($assignment && (int) $assignment['team_id'] !== $teamId) throw new \RuntimeException('O treinador ' . $email . ' ja esta vinculado a outra equipe.');
            if ($assignment) {
                $updateAssignment->execute([$now, (int) $assignment['id']]);
            } else {
                $createAssignment->execute([$teamId, $userId, date('Y-m-d'), $adminId, $now, $now]);
            }
            $linked++;

            $findStaff->execute([$teamId, $displayName]);
            $staffId = (int) $findStaff->fetchColumn();
            if ($staffId) {
                $updateStaff->execute([$userId, $email, $now, $staffId]);
            } else {
                $createStaff->execute([$teamId, $staffRoleId, $userId, $displayName, $displayName, $email, date('Y-m-d'), $now, $now]);
            }
        }
        return ['created' => $created, 'linked' => $linked];
    }

    private static function groups(PDO $pdo, int $phaseId, array $teamIds, string $now): void
    {
        $find = $pdo->prepare('SELECT id, code FROM competition_groups WHERE phase_id = ? ORDER BY display_order');
        $find->execute([$phaseId]);
        $groups = $find->fetchAll();
        if (count($groups) < 2) throw new \RuntimeException('Os grupos da Copa nao foram criados.');
        $pdo->prepare("UPDATE group_teams SET status = 'withdrawn', withdrawn_at = ?, updated_at = ? WHERE phase_id = ? AND team_id NOT IN (" . implode(',', array_fill(0, count($teamIds), '?')) . ")")->execute(array_merge([$now, $now, $phaseId], $teamIds));
        $insert = $pdo->prepare("INSERT INTO group_teams (phase_id, group_id, team_id, position, status, joined_at, updated_at) VALUES (?, ?, ?, ?, 'active', ?, ?) ON DUPLICATE KEY UPDATE group_id = VALUES(group_id), position = VALUES(position), status = 'active', withdrawn_at = NULL, updated_at = VALUES(updated_at)");
        foreach (array_chunk($teamIds, 5) as $groupIndex => $chunk) foreach ($chunk as $position => $teamId) $insert->execute([$phaseId, (int) $groups[$groupIndex]['id'], $teamId, $position + 1, $now, $now]);
    }

    private static function eligibility(PDO $pdo, int $regulationId, int $groupsPhaseId, int $knockoutPhaseId, string $now): void
    {
        $pdo->prepare('DELETE FROM regulation_eligibility_rules WHERE regulation_id = ?')->execute([$regulationId]);
        $pdo->prepare('INSERT INTO regulation_eligibility_rules (regulation_id, source_phase_id, destination_phase_id, minimum_participations, participation_type, require_no_suspension, require_same_team, require_complete_documents, allow_exception, release_permission, reason_required, status, created_at, updated_at) VALUES (?, ?, ?, 1, ?, 1, 1, 1, 0, ?, 1, ?, ?, ?)')->execute([$regulationId, $groupsPhaseId, $knockoutPhaseId, 'played', 'regulations.grant_exception', 'active', $now, $now]);
    }

    private static function assignment(PDO $pdo, int $championshipId, int $adminId, string $now): void
    {
        $accountability = self::userId($pdo, 'prestacao@torneios.local');
        if ($accountability) $pdo->prepare('INSERT IGNORE INTO championship_user_assignments (championship_id, user_id, assignment_type, created_at, created_by) VALUES (?, ?, ?, ?, ?)')->execute([$championshipId, $accountability, 'accountability', $now, $adminId]);
    }

    private static function assets(PDO $pdo, int $championshipId, int $regulationId, string $now): void
    {
        $root = dirname(__DIR__, 2) . '/COPA BRASIL DE TALENTOS 2026';
        $storage = new StorageService();
        $championship = $pdo->query('SELECT logo_path FROM championships WHERE id = ' . $championshipId)->fetch() ?: [];
        if (empty($championship['logo_path'])) {
            $source = $root . '/LOGO COPA BRASIL DE TALENTOS.png';
            if (is_file($source)) {
                $stored = $storage->store(['error' => UPLOAD_ERR_OK, 'size' => filesize($source), 'tmp_name' => $source, 'name' => basename($source)], 'championships/' . $championshipId, ['image/png', 'image/jpeg', 'image/webp'], 12582912);
                $pdo->prepare('UPDATE championships SET logo_path = ?, logo_dark_path = ?, favicon_path = ?, updated_at = ? WHERE id = ?')->execute([$stored['path'], $stored['path'], $stored['path'], $now, $championshipId]);
            }
        }
        $documents = glob($root . '/Regulamento*.pdf') ?: [];
        $document = $documents[0] ?? '';
        if (is_file($document)) {
            $exists = $pdo->prepare('SELECT id FROM regulation_documents WHERE regulation_id = ? AND original_name = ? LIMIT 1');
            $exists->execute([$regulationId, basename($document)]);
            if (!$exists->fetchColumn()) {
                $stored = $storage->store(['error' => UPLOAD_ERR_OK, 'size' => filesize($document), 'tmp_name' => $document, 'name' => basename($document)], 'regulations/' . $regulationId, ['application/pdf'], 10485760);
                $pdo->prepare('INSERT INTO regulation_documents (regulation_id, storage_path, original_name, version_label, visibility, created_at) VALUES (?, ?, ?, ?, ?, ?)')->execute([$regulationId, $stored['path'], basename($document), 'Regulamento oficial recebido', 'public', $now]);
            }
        }
    }

    private static function evidenceChecklist(PDO $pdo, int $championshipId, int $adminId, string $now): void
    {
        $items = [
            ['match', 'Súmula da partida', 'Súmula assinada ou foto da súmula.', 1, 1, 'application/pdf,image/jpeg,image/png,image/webp'],
            ['match', 'Fotos da partida', 'Fotos registradas durante a partida.', 1, 20, 'image/jpeg,image/png,image/webp'],
            ['event_day', 'Fotos do dia do evento', 'Fotos gerais do dia, organização e atividades.', 1, 50, 'image/jpeg,image/png,image/webp'],
            ['event_day', 'Comprovante do local', 'Documento ou registro do local utilizado.', 0, 5, 'image/jpeg,image/png,image/webp,application/pdf'],
            ['event_day', 'Lista de presença', 'Lista de presença ou controle equivalente.', 0, 5, 'image/jpeg,image/png,image/webp,application/pdf'],
        ];
        $exists = $pdo->prepare('SELECT 1 FROM championship_evidence_checklist_items WHERE championship_id = ? AND scope = ? AND name = ? AND deleted_at IS NULL LIMIT 1');
        $insert = $pdo->prepare('INSERT INTO championship_evidence_checklist_items (championship_id, scope, name, description, is_required, is_active, display_order, expected_moment, allowed_mime_types, min_files, max_files, max_file_size_bytes, notes_required, blocks_operation_start, blocks_approval_submission, blocks_document_completion, show_in_accountability, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0, 1, ?, ?, ?)');
        foreach ($items as $order => [$scope, $name, $description, $required, $maxFiles, $mimeTypes]) {
            $exists->execute([$championshipId, $scope, $name]);
            if ($exists->fetchColumn()) continue;
            $insert->execute([$championshipId, $scope, $name, $description, $required, $order + 1, $scope === 'event_day' ? 'event_day' : 'after_match', $mimeTypes, 1, $maxFiles, 12582912, $adminId, $now, $now]);
        }
    }

    private static function upsert(PDO $pdo, string $table, int $id, array $values, string $now): void
    {
        $columns = array_keys($values);
        $sql = 'INSERT INTO ' . $table . ' (regulation_id, ' . implode(', ', $columns) . ', created_at, updated_at) VALUES (?, ' . implode(', ', array_fill(0, count($columns), '?')) . ', ?, ?) ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(static fn (string $column): string => $column . ' = VALUES(' . $column . ')', $columns)) . ', updated_at = VALUES(updated_at)';
        $pdo->prepare($sql)->execute(array_merge([$id], array_values($values), [$now, $now]));
    }

    private static function userId(PDO $pdo, string $email): int
    {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        return (int) $statement->fetchColumn();
    }

    private static function activeAdministratorId(PDO $pdo): int
    {
        $statement = $pdo->query("SELECT u.id FROM users u INNER JOIN user_roles ur ON ur.user_id = u.id INNER JOIN roles r ON r.id = ur.role_id WHERE r.`key` = 'administrator' AND u.status = 'active' ORDER BY u.id LIMIT 1");
        return (int) $statement->fetchColumn();
    }
}
