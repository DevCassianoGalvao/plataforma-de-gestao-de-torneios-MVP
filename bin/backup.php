<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;

$root = dirname(__DIR__); $options = options(array_slice($argv, 1));
$output = (string) ($options['output'] ?? Config::get('BACKUP_DIR', 'storage/backups')); $output = absolutePath($root, $output);
if (!is_dir($output) && !mkdir($output, 0750, true) && !is_dir($output)) fail('Nao foi possivel criar o diretorio de backup.');
$db = (string) (Config::get('DB_NAME') ?: ''); if (preg_match('/^[A-Za-z0-9_]+$/', $db) !== 1) fail('DB_NAME invalido.');
$temporary = $output . DIRECTORY_SEPARATOR . '.backup-' . bin2hex(random_bytes(8)); if (!mkdir($temporary, 0750)) fail('Nao foi possivel preparar o backup.');
$dump = $temporary . DIRECTORY_SEPARATOR . 'database.sql';
$dumpBin = Config::get('MYSQLDUMP_BIN', 'mysqldump') ?: 'mysqldump';
$host = safeShellValue((string) Config::get('DB_HOST', '127.0.0.1')); $port = safeShellValue((string) Config::get('DB_PORT', '3306')); $user = safeShellValue((string) Config::get('DB_USER', 'root'));
$command = escapeshellarg($dumpBin) . ' --protocol=tcp --single-transaction --routines --events --host=' . $host . ' --port=' . $port . ' --user=' . $user . ' ' . $db . ' > ' . escapeshellarg($dump);
$environment = is_array(getenv()) ? getenv() : []; $environment['MYSQL_PWD'] = (string) Config::get('DB_PASS', ''); run($command, $environment);
$name = 'tournament-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.zip'; $archive = $output . DIRECTORY_SEPARATOR . $name;
$zip = new ZipArchive(); if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) fail('Nao foi possivel criar o arquivo ZIP.');
$zip->addFile($dump, 'database.sql'); addDirectory($zip, $root . '/public/uploads', 'public/uploads'); addDirectory($zip, $root . '/storage/private', 'storage/private');
$zip->addFromString('manifest.json', json_encode(['created_at' => date(DATE_ATOM), 'database' => $db, 'contents' => ['database.sql', 'public/uploads', 'storage/private']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); $zip->close(); removeTree($temporary);
$retain = max(1, (int) ($options['retain'] ?? 7)); $archives = glob($output . DIRECTORY_SEPARATOR . 'tournament-*.zip') ?: []; usort($archives, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a)); foreach (array_slice($archives, $retain) as $old) @unlink($old);
if (isset($options['verify'])) verify($archive);
echo 'BACKUP_OK file=' . $archive . ' retained=' . min($retain, count($archives)) . "\n";

function options(array $arguments): array { $result = []; foreach ($arguments as $argument) if (str_starts_with($argument, '--')) { $pair = explode('=', substr($argument, 2), 2); $result[$pair[0]] = $pair[1] ?? true; } return $result; }
function absolutePath(string $root, string $path): string { if (preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1 || str_starts_with($path, '/')) return rtrim($path, '\\/'); return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, trim($path, '/\\')); }
function run(string $command, array $environment): void { $pipes = []; $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $environment); if (!is_resource($process)) fail('Nao foi possivel executar o mysqldump.'); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $code = proc_close($process); if ($code !== 0) fail('mysqldump falhou: ' . trim((string) $error)); }
function addDirectory(ZipArchive $zip, string $directory, string $prefix): void { if (!is_dir($directory)) return; $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)); foreach ($iterator as $file) if ($file->isFile()) $zip->addFile($file->getPathname(), $prefix . '/' . str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1))); }
function verify(string $archive): void { $zip = new ZipArchive(); if ($zip->open($archive) !== true || $zip->locateName('database.sql') === false || $zip->locateName('manifest.json') === false) fail('Verificacao do backup falhou.'); $zip->close(); echo "BACKUP_VERIFY_OK\n"; }
function safeShellValue(string $value): string { if ($value === '' || preg_match('/^[A-Za-z0-9_.:@-]+$/', $value) !== 1) fail('Valor de conexao contem caracteres invalidos.'); return $value; }
function removeTree(string $directory): void { if (!is_dir($directory)) return; $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); foreach ($iterator as $file) $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname()); @rmdir($directory); }
function fail(string $message): never { fwrite(STDERR, "BACKUP_ERROR {$message}\n"); exit(1); }
