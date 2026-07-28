<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '3306');
        $name = Config::get('DB_NAME');
        if ($name === null || $name === '') {
            throw new \RuntimeException('DB_NAME nao configurado.');
        }

        self::$connection = self::connect($host, $port, $name, Config::get('DB_USER', 'root'), Config::get('DB_PASS', ''));
        return self::$connection;
    }

    public static function serverConnection(): PDO
    {
        return self::connect(
            Config::get('DB_HOST', '127.0.0.1'),
            Config::get('DB_PORT', '3306'),
            null,
            Config::get('DB_USER', 'root'),
            Config::get('DB_PASS', ''),
        );
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }

    private static function connect(string $host, string $port, ?string $name, string $user, string $password): PDO
    {
        $dsn = 'mysql:host=' . $host . ';port=' . $port . ($name ? ';dbname=' . $name : '') . ';charset=utf8mb4';
        try {
            return new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $exception) {
            throw new \RuntimeException('Nao foi possivel conectar ao MySQL.', 0, $exception);
        }
    }
}
