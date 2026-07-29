<?php
declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function csrfToken(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }
        return $token;
    }

    public static function verifyCsrf(?string $token): void
    {
        if (!is_string($token) || !hash_equals(self::csrfToken(), $token)) {
            throw new \RuntimeException('CSRF token invalido.');
        }
    }

    public static function rotateCsrf(): void
    {
        Session::forget('_csrf');
    }

    public static function safeLocalPath(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_contains($value, "\0") || str_contains($value, '\\') || !str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return null;
        }
        $parts = parse_url($value);
        if ($parts === false || isset($parts['scheme'], $parts['host'], $parts['user'], $parts['pass'])) {
            return null;
        }
        $path = (string) ($parts['path'] ?? '/');
        if (preg_match('#(^|/)\.\.?(/|$)#', rawurldecode($path)) === 1) {
            return null;
        }
        $localPath = Config::url(Config::stripBasePath($path));
        if (isset($parts['query'])) {
            $localPath .= '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $localPath .= '#' . $parts['fragment'];
        }
        return $localPath;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}
