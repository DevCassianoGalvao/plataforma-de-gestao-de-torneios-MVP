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
}
