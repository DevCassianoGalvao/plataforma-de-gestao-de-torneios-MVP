<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\MigrationRunner;
use App\Database\AuthSeed;
use App\Database\AthleteDocumentTypeSeed;
use App\Database\AthleteSeed;
use App\Database\ChampionshipSeed;
use App\Database\PositionSeed;
use App\Database\RegistrationSeed;
use App\Database\ScheduleSeed;
use App\Database\LineupSeed;
use App\Database\MatchOperationSeed;
use App\Database\DisciplineSeed;
use App\Database\TacticalFormationSeed;
use App\Database\TeamSeed;

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

if ($command === 'db:seed') {
    $password = getenv('SEED_DEMO_PASSWORD') ?: '';
    if ($password === '') {
        fwrite(STDERR, "SEED_DEMO_PASSWORD e obrigatoria para o seed.\n");
        exit(1);
    }
    AuthSeed::run(Database::connection(), $password);
    ChampionshipSeed::run(Database::connection());
    TacticalFormationSeed::run(Database::connection());
    TeamSeed::run(Database::connection());
    PositionSeed::run(Database::connection());
    AthleteDocumentTypeSeed::run(Database::connection());
    AthleteSeed::run(Database::connection());
    RegistrationSeed::run(Database::connection());
    ScheduleSeed::run(Database::connection());
    LineupSeed::run(Database::connection());
    MatchOperationSeed::run(Database::connection());
    DisciplineSeed::run(Database::connection());
    echo "Seed de autenticacao concluido.\n";
    exit(0);
}

echo "Comandos: migrate | migrate:status | db:seed\n";
exit($command === 'help' ? 0 : 1);
