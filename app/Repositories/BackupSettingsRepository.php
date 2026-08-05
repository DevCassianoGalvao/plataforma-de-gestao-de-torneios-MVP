<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Config;
use PDO;

final class BackupSettingsRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function get(): array
    {
        $row = $this->pdo->query('SELECT * FROM application_backup_settings WHERE id=1')->fetch() ?: [];
        if (($row['updated_by'] ?? null) === null && Config::get('BACKUP_STORAGE_PROVIDER', 'local') === 'google_drive') {
            $row['provider'] = 'google_drive';
            $row['google_drive_folder_id'] = Config::get('GOOGLE_DRIVE_FOLDER_ID', '');
        }
        return array_merge([
            'provider' => Config::get('BACKUP_STORAGE_PROVIDER', 'local'),
            'google_drive_folder_link' => '',
            'google_drive_folder_id' => Config::get('GOOGLE_DRIVE_FOLDER_ID', ''),
            'schedule_enabled' => 0,
            'schedule_time' => '03:00',
            'schedule_interval_days' => 1,
        ], $row);
    }

    public function save(array $data, int $userId): void
    {
        $sql = 'UPDATE application_backup_settings SET provider=?, google_drive_folder_link=?, google_drive_folder_id=?, schedule_enabled=?, schedule_time=?, schedule_interval_days=?, updated_by=?, updated_at=? WHERE id=1';
        $this->pdo->prepare($sql)->execute([
            $data['provider'],
            $data['google_drive_folder_link'] ?: null,
            $data['google_drive_folder_id'] ?: null,
            (int) $data['schedule_enabled'],
            $data['schedule_time'],
            (int) $data['schedule_interval_days'],
            $userId,
            date('Y-m-d H:i:s'),
        ]);
    }
}
