<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Security;
use App\Services\PasswordPolicy;
use function Tests\assert_same;
use function Tests\assert_true;

final class AuthTest
{
    public static function run(): void
    {
        assert_same([], PasswordPolicy::validate('Senha1234', 'Senha1234'), 'Senha valida foi rejeitada');
        assert_true(PasswordPolicy::validate('curta', 'curta') !== [], 'Senha curta foi aceita');
        assert_true(PasswordPolicy::validate('somenteletras', 'somenteletras') !== [], 'Senha sem numero foi aceita');
        $token = Security::randomToken();
        assert_true(strlen($token) >= 64, 'Token aleatorio curto');
        assert_same(Security::hashToken($token), Security::hashToken($token), 'Hash de token nao deterministico');
        assert_true(Security::hashToken($token) !== $token, 'Token original foi armazenado como hash');
    }
}
