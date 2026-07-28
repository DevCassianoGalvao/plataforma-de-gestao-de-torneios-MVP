<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class MigrationRunner
{
    public function __construct(private readonly PDO $pdo, private readonly string $directory)
    {
    }

    public function migrate(): array
    {
        $this->ensureControlTable();
        $applied = $this->applied();
        $ran = [];
        foreach ($this->files() as $file) {
            $name = basename($file);
            if (isset($applied[$name])) {
                continue;
            }
            $this->pdo->exec((string) file_get_contents($file));
            $statement = $this->pdo->prepare('INSERT INTO schema_migrations (migration, applied_at) VALUES (?, NOW())');
            $statement->execute([$name]);
            $ran[] = $name;
        }
        return $ran;
    }

    public function status(): array
    {
        $this->ensureControlTable();
        $applied = $this->applied();
        return array_map(static fn (string $file): array => [
            'migration' => basename($file),
            'status' => isset($applied[basename($file)]) ? 'applied' : 'pending',
        ], $this->files());
    }

    private function ensureControlTable(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    private function applied(): array
    {
        $rows = $this->pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        return array_fill_keys(array_map('strval', $rows), true);
    }

    private function files(): array
    {
        $files = glob(rtrim($this->directory, '/\\') . '/*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }
}
