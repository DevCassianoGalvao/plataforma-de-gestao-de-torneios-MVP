<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DisciplineRules;
use function Tests\assert_same;
use function Tests\assert_true;

final class DisciplineTest
{
    public static function run(): void
    {
        assert_true(DisciplineRules::personType('athlete') && DisciplineRules::personType('staff'), 'Pessoa disciplinar invalida');
        assert_true(DisciplineRules::cardType('yellow') && DisciplineRules::cardType('second_yellow') && DisciplineRules::cardType('red'), 'Cartao disciplinar invalido');
        assert_true(!DisciplineRules::cardType('blue'), 'Cartao inexistente aceito');
        assert_same(3, DisciplineRules::matches('3'), 'Quantidade de suspensao invalida');
        assert_same(0, DisciplineRules::matches('0'), 'Suspensao zero aceita');
    }
}
