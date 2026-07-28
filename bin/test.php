<?php
declare(strict_types=1);

if ((getenv('APP_ENV') ?: '') !== 'test') {
    fwrite(STDERR, "APP_ENV=test e obrigatorio para executar o banco descartavel.\n");
    exit(1);
}

$dbName = getenv('DB_NAME') ?: '';
if ($dbName === '' || !preg_match('/(^|_)test($|_)/i', $dbName)) {
    fwrite(STDERR, "DB_NAME deve identificar um banco de teste.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/tests/bootstrap.php';
require dirname(__DIR__) . '/tests/Unit/FoundationTest.php';
require dirname(__DIR__) . '/tests/Unit/AuthTest.php';
require dirname(__DIR__) . '/tests/Unit/ChampionshipTest.php';
require dirname(__DIR__) . '/tests/Unit/TeamTest.php';
require dirname(__DIR__) . '/tests/Unit/AthleteTest.php';
require dirname(__DIR__) . '/tests/Integration/MigrationTest.php';
require dirname(__DIR__) . '/tests/Integration/AuthIntegrationTest.php';
require dirname(__DIR__) . '/tests/Integration/ChampionshipIntegrationTest.php';
require dirname(__DIR__) . '/tests/Integration/TeamIntegrationTest.php';
require dirname(__DIR__) . '/tests/Integration/AthleteIntegrationTest.php';
require dirname(__DIR__) . '/tests/Http/FoundationHttpTest.php';
require dirname(__DIR__) . '/tests/Http/AuthenticationHttpTest.php';
require dirname(__DIR__) . '/tests/Http/ChampionshipHttpTest.php';
require dirname(__DIR__) . '/tests/Http/TeamHttpTest.php';
require dirname(__DIR__) . '/tests/Http/AthleteHttpTest.php';

use App\Core\Database;
use Tests\Http\FoundationHttpTest;
use Tests\Integration\MigrationTest;
use Tests\Unit\FoundationTest;
use Tests\Unit\AuthTest;
use Tests\Unit\ChampionshipTest;
use Tests\Unit\TeamTest;
use Tests\Unit\AthleteTest;
use Tests\Integration\AuthIntegrationTest;
use Tests\Integration\ChampionshipIntegrationTest;
use Tests\Integration\TeamIntegrationTest;
use Tests\Integration\AthleteIntegrationTest;
use Tests\Http\AuthenticationHttpTest;
use Tests\Http\ChampionshipHttpTest;
use Tests\Http\TeamHttpTest;
use Tests\Http\AthleteHttpTest;

$server = Database::serverConnection();
$quoted = '`' . str_replace('`', '``', $dbName) . '`';
$server->exec('CREATE DATABASE IF NOT EXISTS ' . $quoted . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

try {
    FoundationTest::run();
    AuthTest::run();
    ChampionshipTest::run();
    TeamTest::run();
    AthleteTest::run();
    MigrationTest::run();
    AuthIntegrationTest::run();
    ChampionshipIntegrationTest::run();
    TeamIntegrationTest::run();
    AthleteIntegrationTest::run();
    FoundationHttpTest::run();
    AuthenticationHttpTest::run();
    ChampionshipHttpTest::run();
    TeamHttpTest::run();
    AthleteHttpTest::run();
    echo "MVP_TESTS_OK unit=5 integration=5 http=5\n";
} finally {
    Database::disconnect();
    $server->exec('DROP DATABASE IF EXISTS ' . $quoted);
}
