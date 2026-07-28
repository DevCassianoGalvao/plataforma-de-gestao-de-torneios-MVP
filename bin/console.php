<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\MigrationRunner;

$command = $argv[1] ?? 'help';
$runner = new MigrationRunner(Database::connection(), dirname(__DIR__) . '/database/migrations');

if ($command === 'migrate') {
    foreach ($runner->migrate() as $migration) {
        echo "Applied {$migration}\n";
    }
    echo "Migrations concluídas.\n";
    exit(0);
}

if ($command === 'migrate:status') {
    foreach ($runner->status() as $row) {
        echo $row['status'] . "\t" . $row['migration'] . "\n";
    }
    exit(0);
}

echo "Comandos: migrate | migrate:status\n";
exit($command === 'help' ? 0 : 1);
