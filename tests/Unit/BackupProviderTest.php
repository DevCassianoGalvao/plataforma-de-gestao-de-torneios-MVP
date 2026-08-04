<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\GoogleDriveBackupProvider;
use function Tests\assert_true;

final class BackupProviderTest
{
    public static function run(): void
    {
        $result = (new GoogleDriveBackupProvider())->testConnection();
        assert_true(array_key_exists('ok', $result), 'Provedor remoto deve responder estado de conexao');
        assert_true(($result['ok'] ?? true) === false, 'Teste sem credencial nao pode conectar');
    }
}
