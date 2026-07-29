<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;

$root = dirname(__DIR__); $options = options(array_slice($argv, 1)); $archive = (string) ($options['archive'] ?? '');
if ($archive === '' || !isset($options['confirm'])) fail('Use --archive=/caminho/backup.zip --confirm. Restauracao sobrescreve dados.');
$archive = realpath($archive) ?: ''; if ($archive === '' || !is_file($archive)) fail('Arquivo de backup nao encontrado.');
$zip = new ZipArchive(); if ($zip->open($archive) !== true) fail('ZIP de backup invalido.'); validateEntries($zip); if ($zip->locateName('database.sql') === false) fail('Backup sem database.sql.');
$temporary = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'torneios-restore-' . bin2hex(random_bytes(8)); if (!mkdir($temporary, 0700)) fail('Nao foi possivel preparar restauracao.');
try {
    if (!$zip->extractTo($temporary)) fail('Nao foi possivel extrair o backup.'); $zip->close();
    $db = (string) (Config::get('DB_NAME') ?: ''); if (preg_match('/^[A-Za-z0-9_-]+$/', $db) !== 1) fail('DB_NAME invalido.');
    $server = \App\Core\Database::serverConnection(); $server->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', $db) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $mysql = Config::get('MYSQL_BIN', 'mysql') ?: 'mysql'; $host = safeShellValue((string) Config::get('DB_HOST', '127.0.0.1')); $port = safeShellValue((string) Config::get('DB_PORT', '3306')); $user = safeShellValue((string) Config::get('DB_USER', 'root')); $command = escapeshellarg($mysql) . ' --protocol=tcp --host=' . $host . ' --port=' . $port . ' --user=' . $user . ' ' . $db . ' < ' . escapeshellarg($temporary . DIRECTORY_SEPARATOR . 'database.sql'); $environment = is_array(getenv()) ? getenv() : []; $environment['MYSQL_PWD'] = (string) Config::get('DB_PASS', ''); run($command, $environment);
    copyTree($temporary . '/storage/private', $root . '/storage/private'); copyTree($temporary . '/public/uploads', $root . '/public/uploads');
    echo 'RESTORE_OK db=' . $db . "\n";
} finally { removeTree($temporary); }

function options(array $arguments): array { $result = []; foreach ($arguments as $argument) if (str_starts_with($argument, '--')) { $pair = explode('=', substr($argument, 2), 2); $result[$pair[0]] = $pair[1] ?? true; } return $result; }
function validateEntries(ZipArchive $zip): void { for ($i = 0; $i < $zip->numFiles; $i++) { $name = (string) $zip->getNameIndex($i); if ($name === '' || str_contains($name, "\0") || str_starts_with($name, '/') || preg_match('#(^|/)\.\.?(/|$)#', str_replace('\\', '/', $name)) === 1) fail('Entrada insegura no backup: ' . $name); } }
function run(string $command, array $environment): void { $pipes = []; $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment); if (!is_resource($process)) fail('Nao foi possivel executar o mysql.'); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $code = proc_close($process); if ($code !== 0) fail('mysql falhou: ' . trim((string) $error)); }
function safeShellValue(string $value): string { if ($value === '' || preg_match('/^[A-Za-z0-9_.:@-]+$/', $value) !== 1) fail('Valor de conexao contem caracteres invalidos.'); return $value; }
function copyTree(string $source, string $destination): void { if (!is_dir($source)) return; if (!is_dir($destination) && !mkdir($destination, 0750, true) && !is_dir($destination)) fail('Nao foi possivel preparar ' . $destination); $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST); foreach ($iterator as $file) { $target = $destination . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, substr($file->getPathname(), strlen($source) + 1)); if ($file->isDir()) { if (!is_dir($target)) mkdir($target, 0750, true); } elseif (!copy($file->getPathname(), $target)) fail('Nao foi possivel restaurar ' . $target); } }
function removeTree(string $directory): void { if (!is_dir($directory)) return; $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach ($iterator as $file) $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname()); @rmdir($directory); }
function fail(string $message): never { fwrite(STDERR, "RESTORE_ERROR {$message}\n"); exit(1); }
