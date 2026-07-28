<?php
declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

final class Auth
{
    public static function user(): ?array
    {
        $id = Session::get('user_id');
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }
        $user = (new UserRepository(Database::connection()))->findById((int) $id);
        if (!$user || $user['status'] !== 'active') {
            Session::destroy();
            return null;
        }
        return $user;
    }

    public static function authenticated(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::authenticated();
    }

    public static function currentUser(): array
    {
        $user = self::user();
        if (!$user) {
            throw new \RuntimeException('Autenticacao necessaria.');
        }
        return $user;
    }
}
