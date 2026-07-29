<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Config;
use App\Core\Security;
use function Tests\assert_same;
use function Tests\assert_true;

final class FoundationTest
{
    public static function run(): void
    {
        assert_same('/torneio-online', Config::basePath(), 'Base path nao normalizado');
        assert_same('/torneio-online/health', Config::url('/health'), 'URL nao respeita base path');
        assert_same('/health', Config::stripBasePath('/torneio-online/health'), 'Base path nao removido');
        assert_same('https://www.cassianogalvao.com.br/torneio-online/health', Config::absoluteUrl('/health'), 'URL absoluta nao respeita dominio');
        assert_same('&lt;script&gt;', Security::escape('<script>'), 'Escape HTML falhou');
        assert_true(strlen(Security::csrfToken()) >= 32, 'CSRF token curto');
    }
}
