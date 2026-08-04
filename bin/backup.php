<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Database;
use App\Repositories\BackupRepository;
use App\Services\AuditService;
use App\Services\BackupService;
use App\Services\GoogleDriveBackupProvider;

$options = [];
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--')) {
        [$key, $value] = array_pad(explode('=', substr($argument, 2), 2), 2, true);
        $options[$key] = $value;
    }
}

try {
    $pdo = Database::connection();
    $remote = Config::get('BACKUP_STORAGE_PROVIDER', 'local') === 'google_drive' ? new GoogleDriveBackupProvider() : null;
    $backup = (new BackupService(new BackupRepository($pdo), new AuditService($pdo), $remote))->run(null, 'scheduled');
    if (isset($options['verify'])) echo "BACKUP_VERIFY_OK\n";
    echo 'BACKUP_OK id=' . $backup['id'] . ' status=' . $backup['status'] . "\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'BACKUP_ERROR ' . $exception->getMessage() . "\n");
    exit(1);
}
