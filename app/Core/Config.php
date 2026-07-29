<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    public static function get(string $key, ?string $default = null): ?string
    {
        return Env::get($key, $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return filter_var(self::get($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function basePath(): string
    {
        $path = trim(self::get('APP_BASE_PATH', '') ?? '');
        if ($path === '' || $path === '/') {
            return '';
        }
        if (str_contains($path, '..') || !str_starts_with($path, '/')) {
            throw new \RuntimeException('APP_BASE_PATH invalido.');
        }
        return '/' . trim($path, '/');
    }

    public static function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return self::basePath() . ($path === '/' ? '/' : $path);
    }

    public static function absoluteUrl(string $path = '/'): string
    {
        $configured = trim(self::get('APP_URL', '') ?? '');
        $parts = $configured !== '' ? parse_url($configured) : false;
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return self::url($path);
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }
        return rtrim($origin, '/') . self::url($path);
    }

    public static function stripBasePath(string $path): string
    {
        $base = self::basePath();
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base));
        }
        return '/' . ltrim($path, '/');
    }
}
