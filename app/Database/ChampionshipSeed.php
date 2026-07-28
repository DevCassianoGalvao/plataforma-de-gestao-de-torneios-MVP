<?php
declare(strict_types=1);

namespace App\Database;

use PDO;

final class ChampionshipSeed
{
    public static function run(PDO $pdo): void
    {
        if (getenv('APP_ENV') === 'production') {
            throw new \RuntimeException('Seed de demonstracao bloqueado em producao.');
        }
        $now = date('Y-m-d H:i:s');
        $season = self::firstOrCreate($pdo, 'SELECT id FROM seasons WHERE name = ? AND year = ? AND deleted_at IS NULL', ['Temporada 2026', 2026], 'INSERT INTO seasons (name, year, starts_at, ends_at, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)', ['Temporada 2026', 2026, '2026-01-01', '2026-12-31', 'active', $now, $now]);
        $category = self::firstOrCreate($pdo, 'SELECT id FROM categories WHERE slug = ? AND deleted_at IS NULL', ['sub-15-masculino'], 'INSERT INTO categories (name, slug, description, minimum_age, maximum_age, gender_rule, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', ['Sub-15 Masculino', 'sub-15-masculino', 'Categoria ficticia de demonstracao.', 12, 15, 'male', 'active', $now, $now]);
        $findChampionship = $pdo->prepare('SELECT id FROM championships WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
        $findChampionship->execute(['copa-brasil-de-talentos-2026']);
        $championshipId = (int) $findChampionship->fetchColumn();
        $adminId = self::userId($pdo, 'admin@torneios.local');
        if (!$championshipId) {
            $statement = $pdo->prepare('INSERT INTO championships (name, short_name, slug, description, season_id, category_id, starts_at, ends_at, registration_starts_at, registration_ends_at, status, visibility, default_theme, primary_color, secondary_color, accent_color, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $statement->execute(['Copa Brasil de Talentos 2026', 'Copa Brasil', 'copa-brasil-de-talentos-2026', 'Campeonato ficticio para validar a configuracao inicial do MVP.', $season, $category, '2026-06-01', '2026-08-30', '2026-04-01', '2026-05-20', 'configured', 'private', 'light', '#123C32', '#245C4A', '#D9A441', $adminId, $now, $now]);
            $championshipId = (int) $pdo->lastInsertId();
        }
        $regulation = self::firstOrCreate($pdo, 'SELECT id FROM regulations WHERE championship_id = ? AND version_number = 1 LIMIT 1', [$championshipId], 'INSERT INTO regulations (championship_id, version_number, name, status, effective_from, published_at, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$championshipId, 1, 'Regulamento base Copa Brasil de Talentos', 'published', '2026-01-01', $now, $adminId, $now, $now]);
        $preset = [
            ['regulation_format_settings', ['group_count' => 2, 'teams_per_group' => 5, 'qualified_per_group' => 4, 'group_rounds' => 'single', 'home_and_away' => 0, 'knockout_starts_at' => 'quarterfinals', 'third_place_match' => 0, 'final_format' => 'single_match']],
            ['regulation_points_settings', ['points_win' => 3, 'points_draw' => 1, 'points_loss' => 0, 'wo_winner_goals' => 3, 'wo_loser_goals' => 0]],
            ['regulation_discipline_settings', ['yellow_cards_for_suspension' => 3, 'yellow_suspension_matches' => 1, 'red_card_automatic_suspension' => 1, 'red_card_suspension_matches' => 1, 'reset_cards_enabled' => 0, 'reset_cards_stage' => '']],
            ['regulation_match_settings', ['regular_time_minutes' => 40, 'halftime_minutes' => 10, 'substitutions_allowed' => 5, 'substitution_windows' => 3, 'extra_time_enabled' => 0, 'extra_time_minutes' => 10, 'penalty_shootout_enabled' => 1, 'direct_penalties' => 0]],
        ];
        foreach ($preset as [$table, $values]) {
            $columns = array_keys($values);
            $sql = 'INSERT INTO ' . $table . ' (regulation_id, ' . implode(', ', $columns) . ', created_at, updated_at) VALUES (?, ' . implode(', ', array_fill(0, count($columns), '?')) . ', ?, ?) ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(static fn (string $column): string => $column . ' = VALUES(' . $column . ')', $columns)) . ', updated_at = VALUES(updated_at)';
            $pdo->prepare($sql)->execute(array_merge([$regulation], array_values($values), [$now, $now]));
        }
        $criteria = ['wins', 'goal_difference', 'goals_scored', 'head_to_head', 'fewer_cards', 'administrative_decision', 'draw_lots'];
        $insertCriterion = $pdo->prepare('INSERT INTO regulation_tiebreakers (regulation_id, criterion, priority, enabled, created_at) VALUES (?, ?, ?, 1, ?) ON DUPLICATE KEY UPDATE priority = VALUES(priority), enabled = 1');
        foreach ($criteria as $index => $criterion) {
            $insertCriterion->execute([$regulation, $criterion, $index + 1, $now]);
        }
        $organizerId = self::userId($pdo, 'organizador@torneios.local');
        $assignment = $pdo->prepare('INSERT IGNORE INTO championship_user_assignments (championship_id, user_id, assignment_type, created_at, created_by) VALUES (?, ?, ?, ?, ?)');
        $assignment->execute([$championshipId, $organizerId, 'organizer', $now, $adminId]);
    }

    private static function firstOrCreate(PDO $pdo, string $findSql, array $findParams, string $insertSql, array $insertParams): int
    {
        $find = $pdo->prepare($findSql);
        $find->execute($findParams);
        $id = (int) $find->fetchColumn();
        if ($id) {
            return $id;
        }
        $insert = $pdo->prepare($insertSql);
        $insert->execute($insertParams);
        return (int) $pdo->lastInsertId();
    }

    private static function userId(PDO $pdo, string $email): int
    {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $id = (int) $statement->fetchColumn();
        if (!$id) {
            throw new \RuntimeException('Usuario necessario ao seed nao encontrado: ' . $email);
        }
        return $id;
    }
}
