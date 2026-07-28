<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
use App\Support\Database;
$db=Database::connection(); $db->exec('CREATE TABLE IF NOT EXISTS migrations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,migration VARCHAR(190) NOT NULL UNIQUE,applied_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
foreach (glob(dirname(__DIR__).'/database/migrations/*.sql') as $file) { $name=basename($file); $s=$db->prepare('SELECT COUNT(*) FROM migrations WHERE migration=?'); $s->execute([$name]); if ((int)$s->fetchColumn()) continue; $db->exec(file_get_contents($file)); $db->prepare('INSERT INTO migrations(migration,applied_at) VALUES(?,NOW())')->execute([$name]); echo "Applied $name\n"; }
