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
        assert_same(['0001_foundation.sql', '0002_authentication.sql'], $first, 'Migrations nao aplicadas');
        assert_same([], $runner->migrate(), 'Migration nao idempotente');
        $status = $runner->status();
        assert_same('applied', $status[0]['status'] ?? null, 'Status da migration incorreto');
        $health = Database::connection()->query('SELECT status FROM foundation_health WHERE id = 1')->fetchColumn();
        assert_true($health === 'ok', 'Health check nao persistido');
        foreach (['users', 'roles', 'permissions', 'role_permissions', 'user_roles', 'password_reset_tokens', 'login_attempts', 'audit_logs'] as $table) {
            assert_true((bool) Database::connection()->query('SHOW TABLES LIKE ' . Database::connection()->quote($table))->fetchColumn(), 'Tabela ausente: ' . $table);
        }
    }
}
