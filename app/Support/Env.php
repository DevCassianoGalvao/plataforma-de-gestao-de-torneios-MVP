<?php
declare(strict_types=1);
namespace App\Support;

final class Env {
    private static array $values = [];
    public static function load(string $file): void {
        if (!is_file($file)) return;
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line); if ($line === '' || str_starts_with($line, '#')) continue;
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            self::$values[trim($key)] = trim($value, " \t\"'");
        }
    }
    public static function get(string $key, mixed $default = null): mixed { $environment=getenv($key); return self::$values[$key] ?? $_ENV[$key] ?? ($environment===false?$default:$environment); }
    public static function bool(string $key, bool $default = false): bool { return filter_var(self::get($key, $default), FILTER_VALIDATE_BOOLEAN); }
}
