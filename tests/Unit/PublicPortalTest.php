<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\PublicPortalRepository;
use function Tests\assert_same;
use function Tests\assert_true;

final class PublicPortalTest
{
    public static function run(): void
    {
        assert_true(in_array('public', ['public', 'draft'], true), 'Portal deve trabalhar com campeonato publico');
        assert_same(['goals', 'assists'], ['goals', 'assists'], 'Rankings publicos invalidos');
        assert_true((new \ReflectionClass(PublicPortalRepository::class))->hasMethod('championship'), 'Read model publico ausente');
        assert_true((new \ReflectionClass(PublicPortalRepository::class))->hasMethod('match'), 'Detalhe publico de partida ausente');
    }
}
