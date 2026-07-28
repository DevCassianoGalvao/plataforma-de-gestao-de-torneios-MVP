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
require dirname(__DIR__) . '/tests/Integration/MigrationTest.php';
require dirname(__DIR__) . '/tests/Integration/AuthIntegrationTest.php';
require dirname(__DIR__) . '/tests/Http/FoundationHttpTest.php';
require dirname(__DIR__) . '/tests/Http/AuthenticationHttpTest.php';

use App\Core\Database;
use Tests\Http\FoundationHttpTest;
use Tests\Integration\MigrationTest;
use Tests\Unit\FoundationTest;
use Tests\Unit\AuthTest;
use Tests\Integration\AuthIntegrationTest;
use Tests\Http\AuthenticationHttpTest;

$server = Database::serverConnection();
$quoted = '`' . str_replace('`', '``', $dbName) . '`';
$server->exec('CREATE DATABASE IF NOT EXISTS ' . $quoted . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

try {
    FoundationTest::run();
    AuthTest::run();
    MigrationTest::run();
    AuthIntegrationTest::run();
    FoundationHttpTest::run();
    AuthenticationHttpTest::run();
    echo "AUTH_TESTS_OK unit=2 integration=2 http=2\n";
} finally {
    Database::disconnect();
    $server->exec('DROP DATABASE IF EXISTS ' . $quoted);
}
