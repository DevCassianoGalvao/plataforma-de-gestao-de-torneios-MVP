<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\MigrationRunner;
use function Tests\assert_same;
use function Tests\assert_true;

final class MigrationTest
{
    public static function run(): void
    {
        $runner = new MigrationRunner(Database::connection(), dirname(__DIR__, 2) . '/database/migrations');
        $first = $runner->migrate();
        assert_same(['0001_foundation.sql', '0002_authentication.sql', '0003_championships_and_regulations.sql', '0004_teams_staff_and_formations.sql'], $first, 'Migrations nao aplicadas');
        assert_same([], $runner->migrate(), 'Migration nao idempotente');
        $status = $runner->status();
        assert_same('applied', $status[0]['status'] ?? null, 'Status da migration incorreto');
        $health = Database::connection()->query('SELECT status FROM foundation_health WHERE id = 1')->fetchColumn();
        assert_true($health === 'ok', 'Health check nao persistido');
        foreach (['users', 'roles', 'permissions', 'role_permissions', 'user_roles', 'password_reset_tokens', 'login_attempts', 'audit_logs', 'seasons', 'categories', 'championships', 'championship_user_assignments', 'regulations', 'regulation_format_settings', 'regulation_points_settings', 'regulation_tiebreakers', 'regulation_discipline_settings', 'regulation_match_settings', 'regulation_documents', 'staff_roles', 'tactical_formations', 'tactical_formation_slots', 'teams', 'team_user_assignments', 'team_staff'] as $table) {
            assert_true((bool) Database::connection()->query('SHOW TABLES LIKE ' . Database::connection()->quote($table))->fetchColumn(), 'Tabela ausente: ' . $table);
        }
    }
}
