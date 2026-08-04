<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\BackupRepository;
use ZipArchive;

final class BackupService
{
    public function __construct(private readonly BackupRepository $backups, private readonly AuditService $audit, private readonly ?BackupRemoteProvider $remote = null) {}

    public function run(?int $userId = null, string $type = 'manual'): array
    {
        if (!Config::bool('BACKUP_ENABLED', true)) throw new \RuntimeException('Backups estao desativados nesta instalacao.');
        $dir = $this->directory();
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) throw new \RuntimeException('Nao foi possivel preparar diretorio de backup.');
        $lock = fopen($dir . DIRECTORY_SEPARATOR . '.backup.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) throw new \RuntimeException('Ja existe backup em execucao.');
        $started = microtime(true); $key = 'backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
        $id = $this->backups->create(['backup_key' => $key, 'type' => $type, 'provider' => $this->providerName(), 'user_id' => $userId]);
        $temporary = $dir . DIRECTORY_SEPARATOR . '.' . $key;
        try {
            if (!mkdir($temporary, 0750, true) && !is_dir($temporary)) throw new \RuntimeException('Nao foi possivel preparar arquivos temporarios.');
            $dump = $temporary . DIRECTORY_SEPARATOR . 'database.sql'; $this->dumpDatabase($dump);
            $archive = $dir . DIRECTORY_SEPARATOR . $key . '.zip'; $this->archive($temporary, $dump, $archive, $key);
            $hash = hash_file('sha256', $archive); if ($hash === false || !$this->verify($archive)) throw new \RuntimeException('Verificacao local do arquivo falhou.');
            $data = ['status' => 'completed', 'local_status' => 'completed', 'validation_status' => 'valid', 'remote_status' => $this->remote ? 'pending' : 'not_configured', 'local_path' => $archive, 'size_bytes' => filesize($archive) ?: 0, 'sha256' => $hash, 'duration_seconds' => (int) round(microtime(true) - $started), 'attempts' => 0, 'completed_at' => date('Y-m-d H:i:s')];
            $this->backups->update($id, $data);
            if ($this->remote) $this->sendRemote($id, $archive, $key . '.zip', $hash);
            $row = $this->backups->find($id) ?? throw new \RuntimeException('Backup nao encontrado apos criacao.');
            $this->audit->record('backup.completed', $userId, 'application_backup', (string) $id, ['backup_key' => $key, 'remote_status' => $row['remote_status']], null);
            $this->applyRetention(); return $row;
        } catch (\Throwable $e) {
            $this->backups->update($id, ['status' => 'failed', 'local_status' => 'failed', 'validation_status' => 'failed', 'error_message' => $this->message($e), 'duration_seconds' => (int) round(microtime(true) - $started), 'completed_at' => date('Y-m-d H:i:s')]);
            $this->audit->record('backup.failed', $userId, 'application_backup', (string) $id, ['reason' => $this->message($e)], null); throw $e;
        } finally { $this->remove($temporary); if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); } }
    }

    public function retryRemote(int $id, ?int $userId = null): array
    {
        $row = $this->backups->find($id) ?? throw new \RuntimeException('Backup nao encontrado.');
        if (!$this->remote) throw new \RuntimeException('Destino remoto nao configurado.');
        $path = $this->safePath((string) $row['local_path']);
        if (!is_file($path) || !hash_equals((string) $row['sha256'], (string) hash_file('sha256', $path))) throw new \RuntimeException('Arquivo local ausente ou invalido.');
        if (!empty($row['remote_id']) && $this->remote->exists((string) $row['remote_id'])) return $row;
        $this->sendRemote($id, $path, basename($path), (string) $row['sha256']); $this->audit->record('backup.remote_retried', $userId, 'application_backup', (string) $id, [], null);
        return $this->backups->find($id) ?? $row;
    }

    public function testRemote(): array { return $this->remote ? $this->remote->testConnection() : ['ok' => false, 'error' => 'Destino remoto nao configurado.']; }
    public function file(int $id): array { $row = $this->backups->find($id) ?? throw new \RuntimeException('Backup nao encontrado.'); $path = $this->safePath((string) $row['local_path']); if (!is_file($path)) throw new \RuntimeException('Arquivo local nao esta mais disponivel.'); return [$row, $path]; }
    public function delete(int $id, int $userId): void { $row = $this->backups->find($id) ?? throw new \RuntimeException('Backup nao encontrado.'); if (!empty($row['remote_id']) && $this->remote) $this->remote->delete((string) $row['remote_id']); $path = $this->safePath((string) $row['local_path']); if (is_file($path)) @unlink($path); $this->backups->softDelete($id, $userId); $this->audit->record('backup.deleted', $userId, 'application_backup', (string) $id, [], null); }

    private function sendRemote(int $id, string $path, string $name, string $hash): void { $result = $this->remote?->upload($path, $name, $hash) ?? ['ok' => false, 'error' => 'Destino remoto ausente.']; $attempts = (int) (($this->backups->find($id)['attempts'] ?? 0) + 1); if (($result['ok'] ?? false) && !empty($result['id'])) { $this->backups->update($id, ['remote_status' => 'completed', 'remote_id' => (string) $result['id'], 'remote_path' => $name, 'attempts' => $attempts, 'error_message' => null]); return; } $this->backups->update($id, ['status' => 'partially_completed', 'remote_status' => 'failed', 'attempts' => $attempts, 'error_message' => (string) ($result['error'] ?? 'Falha no envio remoto.')]); }
    private function directory(): string { $root = dirname(__DIR__, 2); $configured = trim((string) Config::get('BACKUP_DIR', 'storage/backups')); $path = preg_match('#^(?:[A-Za-z]:[\\\\/]|/)#', $configured) ? $configured : $root . DIRECTORY_SEPARATOR . trim(str_replace(['/', '\\\\'], DIRECTORY_SEPARATOR, $configured), DIRECTORY_SEPARATOR); $storage = realpath($root . DIRECTORY_SEPARATOR . 'storage') ?: ($root . DIRECTORY_SEPARATOR . 'storage'); if (!str_starts_with(str_replace('\\\\', '/', $path), str_replace('\\\\', '/', $storage))) throw new \RuntimeException('BACKUP_DIR deve ficar dentro de storage.'); return rtrim($path, '\\\\/'); }
    private function safePath(string $path): string { $real = realpath($path); $base = realpath($this->directory()); if ($real === false || $base === false || !str_starts_with(str_replace('\\\\', '/', $real), str_replace('\\\\', '/', $base) . '/')) throw new \RuntimeException('Caminho de backup invalido.'); return $real; }
    private function dumpDatabase(string $dump): void { $db = (string) Config::get('DB_NAME', ''); if (preg_match('/^[A-Za-z0-9_-]+$/', $db) !== 1) throw new \RuntimeException('Nome do banco invalido.'); $bin = (string) Config::get('MYSQLDUMP_BIN', 'mysqldump'); $parts = [escapeshellarg($bin), '--protocol=tcp', '--single-transaction', '--routines', '--events', '--host=' . escapeshellarg((string) Config::get('DB_HOST', '127.0.0.1')), '--port=' . escapeshellarg((string) Config::get('DB_PORT', '3306')), '--user=' . escapeshellarg((string) Config::get('DB_USER', 'root')), escapeshellarg($db)]; $env = is_array(getenv()) ? getenv() : []; $env['MYSQL_PWD'] = (string) Config::get('DB_PASS', ''); $process = proc_open(implode(' ', $parts), [1 => ['file', $dump, 'w'], 2 => ['pipe', 'w']], $pipes, null, $env); if (!is_resource($process)) throw new \RuntimeException('Nao foi possivel iniciar mysqldump.'); $error = stream_get_contents($pipes[2]); fclose($pipes[2]); if (proc_close($process) !== 0) throw new \RuntimeException('mysqldump falhou: ' . trim((string) $error)); }
    private function archive(string $temporary, string $dump, string $archive, string $key): void { $zip = new ZipArchive(); if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new \RuntimeException('Nao foi possivel criar arquivo ZIP.'); $zip->addFile($dump, 'database.sql'); $this->addDirectory($zip, dirname(__DIR__, 2) . '/public/uploads', 'public/uploads'); $this->addDirectory($zip, dirname(__DIR__, 2) . '/storage/private', 'storage/private'); $zip->addFromString('manifest.json', json_encode(['backup_key' => $key, 'created_at' => date(DATE_ATOM), 'contents' => ['database.sql', 'public/uploads', 'storage/private']], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); $zip->close(); }
    private function addDirectory(ZipArchive $zip, string $dir, string $prefix): void { if (!is_dir($dir)) return; $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)); foreach ($it as $file) if ($file->isFile() && !str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR)) $zip->addFile($file->getPathname(), $prefix . '/' . str_replace('\\\\', '/', substr($file->getPathname(), strlen($dir) + 1))); }
    private function verify(string $archive): bool { $zip = new ZipArchive(); $ok = $zip->open($archive) === true && $zip->locateName('database.sql') !== false && $zip->locateName('manifest.json') !== false; if ($zip->status === 0) $zip->close(); return $ok; }
    private function applyRetention(): void { $days = max(1, (int) Config::get('BACKUP_RETENTION_DAYS', '14')); foreach ($this->backups->list() as $row) { if ((string) $row['status'] !== 'completed' || empty($row['completed_at']) || strtotime((string) $row['completed_at']) >= strtotime('-' . $days . ' days')) continue; $path = $this->safePath((string) $row['local_path']); if (is_file($path)) @unlink($path); } }
    private function remove(string $dir): void { if (!is_dir($dir)) return; $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($it as $item) $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname()); @rmdir($dir); }
    private function providerName(): ?string { return $this->remote ? 'google_drive' : null; }
    private function message(\Throwable $e): string { return mb_substr(preg_replace('/(?:token|password|secret)=\\S+/i', 'dado protegido', $e->getMessage()) ?: 'Falha no backup.', 0, 1100); }
}
