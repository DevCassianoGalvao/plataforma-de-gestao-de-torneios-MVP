<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\DeploymentIssue;
use App\Core\Response;
use App\Core\Security;
use App\Services\StorageService;
use function Tests\assert_same;
use function Tests\assert_true;

final class ProductionReadinessTest
{
    public static function run(): void
    {
        assert_same('/torneio-online/admin', Security::safeLocalPath('/torneio-online/admin'), 'Rota local valida foi rejeitada');
        assert_same(null, Security::safeLocalPath('https://evil.example/'), 'Open redirect externo aceito');
        assert_same(null, Security::safeLocalPath('//evil.example/'), 'Open redirect protocol-relative aceito');
        assert_same(null, Security::safeLocalPath('/torneio-online/../admin'), 'Traversal em redirect aceito');
        $headers = Response::html('ok')->headers;
        foreach (['Content-Security-Policy', 'X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy', 'Permissions-Policy'] as $header) {
            assert_true(isset($headers[$header]), 'Header de seguranca ausente: ' . $header);
        }
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'torneios-security-' . bin2hex(random_bytes(4));
        mkdir($root, 0700, true);
        $storage = new StorageService($root);
        try {
            assert_same(null, $storage->read('../outside.txt'), 'Traversal de leitura aceito');
            try {
                $storage->store(['error' => UPLOAD_ERR_OK, 'size' => 1, 'tmp_name' => __FILE__, 'name' => 'x.txt'], '../escape', ['text/plain'], 1000);
                throw new \RuntimeException('Traversal de upload aceito');
            } catch (\RuntimeException $exception) {
                assert_true(str_contains($exception->getMessage(), 'Caminho') || str_contains($exception->getMessage(), 'Tipo'), 'Erro inesperado ao bloquear upload');
            }
        } finally {
            self::removeTree($root);
        }
        $missingTable = new \PDOException('Table does not exist');
        $missingTable->errorInfo = ['42S02', 1146, 'Table does not exist'];
        assert_true(DeploymentIssue::requiresDatabaseUpdate($missingTable), 'Schema desatualizado nao identificado');
        assert_true(!DeploymentIssue::requiresDatabaseUpdate(new \RuntimeException('Falha generica')), 'Falha generica marcada como migration');
    }

    private static function removeTree(string $directory): void
    {
        if (!is_dir($directory)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        @rmdir($directory);
    }
}
