<?php
declare(strict_types=1);
namespace App\Support;

use PDO;
use PDOException;

final class Database {
    private static ?PDO $pdo = null;
    public static function connection(): PDO {
        if (self::$pdo) return self::$pdo;
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', Env::get('DB_HOST','127.0.0.1'), Env::get('DB_PORT','3306'), Env::get('DB_NAME','torneios'));
        try { self::$pdo = new PDO($dsn, (string) Env::get('DB_USER','root'), (string) Env::get('DB_PASS',''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]); }
        catch (PDOException $e) { throw new \RuntimeException('Database connection failed.', 0, $e); }
        return self::$pdo;
    }
}
