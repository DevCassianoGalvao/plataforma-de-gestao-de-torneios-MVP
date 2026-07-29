<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$options = options(array_slice($argv, 1));
$envPath = $root . DIRECTORY_SEPARATOR . '.env';
$examplePath = $root . DIRECTORY_SEPARATOR . '.env.example';

if (isset($options['create-env'])) {
    if (is_file($envPath)) fail('O arquivo .env ja existe.');
    if (!copy($examplePath, $envPath)) fail('Nao foi possivel criar .env a partir do exemplo.');
    echo "ENV_CREATED edite .env, gere APP_KEY e execute novamente.\n";
    exit(2);
}
if (!is_file($envPath)) fail(' .env ausente. Execute php bin/install.php --create-env e configure o ambiente.');

$env = readEnv($envPath);
$environment = $env['APP_ENV'] ?? 'development';
if ($environment === 'production') {
    if (($env['APP_DEBUG'] ?? 'false') !== 'false') fail('APP_DEBUG deve ser false em producao.');
    $key = $env['APP_KEY'] ?? '';
    $decoded = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : false;
    if (!is_string($decoded) || strlen($decoded) < 32 || str_contains($key, 'generate-a-32-byte-key')) fail('APP_KEY base64 com pelo menos 32 bytes e obrigatoria em producao.');
    if (($env['SEED_DEMO_PASSWORD'] ?? '') !== '') fail('Remova SEED_DEMO_PASSWORD do .env de producao.');
}

require $root . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\MigrationRunner;
use App\Database\AthleteDocumentTypeSeed;
use App\Database\AthleteSeed;
use App\Database\AuthSeed;
use App\Database\ChampionshipSeed;
use App\Database\DisciplineSeed;
use App\Database\LineupSeed;
use App\Database\MatchOperationSeed;
use App\Database\NewsSeed;
use App\Database\PositionSeed;
use App\Database\RegistrationSeed;
use App\Database\ScheduleSeed;
use App\Database\TacticalFormationSeed;
use App\Database\TeamSeed;
use App\Database\TransferSeed;

$dbName = (string) (getenv('DB_NAME') ?: '');
if (preg_match('/^[A-Za-z0-9_]+$/', $dbName) !== 1) fail('DB_NAME invalido. Use somente letras, numeros e underscore.');
$server = Database::serverConnection();
$quoted = '`' . str_replace('`', '``', $dbName) . '`';
$server->exec('CREATE DATABASE IF NOT EXISTS ' . $quoted . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo = Database::connection();
$runner = new MigrationRunner($pdo, $root . '/database/migrations');
$applied = $runner->migrate();
foreach ($applied as $migration) echo "Applied {$migration}\n";

$directories = ['storage/logs', 'storage/sessions', 'storage/cache', 'storage/private', 'storage/exports', 'public/uploads'];
foreach ($directories as $directory) {
    $absolute = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $directory);
    if (!is_dir($absolute) && !mkdir($absolute, 0750, true) && !is_dir($absolute)) fail('Nao foi possivel criar ' . $directory);
    if (!is_writable($absolute)) fail($directory . ' nao possui permissao de escrita.');
}

$seeded = false;
if (isset($options['seed'])) {
    if ($environment === 'production') fail('Seed demonstrativo bloqueado em producao.');
    $password = (string) (getenv('SEED_DEMO_PASSWORD') ?: '');
    if ($password === '') fail('SEED_DEMO_PASSWORD e obrigatoria para --seed.');
    AuthSeed::run($pdo, $password);
    ChampionshipSeed::run($pdo);
    TacticalFormationSeed::run($pdo);
    TeamSeed::run($pdo);
    PositionSeed::run($pdo);
    AthleteDocumentTypeSeed::run($pdo);
    AthleteSeed::run($pdo);
    RegistrationSeed::run($pdo);
    ScheduleSeed::run($pdo);
    LineupSeed::run($pdo);
    MatchOperationSeed::run($pdo);
    DisciplineSeed::run($pdo);
    NewsSeed::run($pdo);
    TransferSeed::run($pdo);
    $seeded = true;
}

$smokeUrl = (string) ($options['smoke-url'] ?? '');
if ($smokeUrl !== '') smoke($smokeUrl, (string) ($env['INSTALL_TEST_EMAIL'] ?? ''), (string) ($env['INSTALL_TEST_PASSWORD'] ?? ''), (string) ($env['INSTALL_PORTAL_SLUG'] ?? 'copa-brasil-de-talentos-2026'));
echo 'INSTALL_OK db=' . $dbName . ' migrations=' . count($applied) . ' seed=' . ($seeded ? 'yes' : 'no') . "\n";

function options(array $arguments): array
{
    $result = [];
    foreach ($arguments as $argument) {
        if (str_starts_with($argument, '--')) {
            $pair = explode('=', substr($argument, 2), 2);
            $result[$pair[0]] = $pair[1] ?? true;
        }
    }
    return $result;
}

function readEnv(string $path): array
{
    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim($value, " \t\"'");
    }
    return $values;
}

function smoke(string $base, string $email, string $password, string $slug): void
{
    if (!extension_loaded('curl')) fail('Extensao cURL obrigatoria para --smoke-url.');
    $base = rtrim($base, '/'); $jar = tempnam(sys_get_temp_dir(), 'torneios-install-');
    try {
        $login = request($base . '/login', 'GET', [], $jar); if ($login['status'] !== 200) fail('Smoke login GET falhou.');
        if ($email === '' || $password === '') fail('INSTALL_TEST_EMAIL e INSTALL_TEST_PASSWORD sao obrigatorios para smoke de login.');
        preg_match('/name="_csrf" value="([^"]+)"/', $login['body'], $match); $csrf = html_entity_decode($match[1] ?? '', ENT_QUOTES, 'UTF-8');
        if ($csrf === '') fail('CSRF nao encontrado na tela de login.');
        $auth = request($base . '/login', 'POST', ['_csrf' => $csrf, 'email' => $email, 'password' => $password], $jar);
        if ($auth['status'] !== 302 || str_contains(strtolower($auth['headers']), 'location: https://')) fail('Smoke de login falhou.');
        $portal = request($base . '/campeonatos/' . rawurlencode($slug), 'GET', [], $jar);
        if ($portal['status'] !== 200 || str_contains($portal['body'], 'private_notes')) fail('Smoke do portal falhou ou vazou campo privado.');
    } finally { if (is_string($jar) && is_file($jar)) @unlink($jar); }
    echo "SMOKE_OK login=1 portal=1\n";
}

function request(string $url, string $method, array $fields, string $jar): array
{
    $handle = curl_init($url); curl_setopt_array($handle, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar, CURLOPT_POSTFIELDS => $method === 'POST' ? http_build_query($fields) : null, CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'], CURLOPT_FOLLOWLOCATION => false]);
    $raw = (string) curl_exec($handle); $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE); $length = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE); curl_close($handle);
    return ['status' => $status, 'headers' => substr($raw, 0, $length), 'body' => substr($raw, $length)];
}

function fail(string $message): never
{
    fwrite(STDERR, "INSTALL_ERROR {$message}\n"); exit(1);
}
