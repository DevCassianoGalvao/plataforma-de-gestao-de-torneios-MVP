<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MatchOperationRules;
use function Tests\assert_same;
use function Tests\assert_true;

final class MatchOperationTest
{
    public static function run(): void
    {
        assert_true(MatchOperationRules::eventType('goal'), 'Gol nao reconhecido');
        assert_true(MatchOperationRules::eventType('own_goal'), 'Gol contra nao reconhecido');
        assert_true(!MatchOperationRules::eventType('score'), 'Tipo de evento inexistente aceito');
        assert_true(MatchOperationRules::period('penalties'), 'Periodo de penaltis nao reconhecido');
        assert_true(!MatchOperationRules::period('minute_by_minute'), 'Cronologia indevida aceita como periodo');
        assert_same(0, MatchOperationRules::minute('0'), 'Minuto zero deveria ser valido');
        assert_same(300, MatchOperationRules::minute('300'), 'Limite de minuto deveria ser valido');
        assert_same(null, MatchOperationRules::minute('301'), 'Minuto acima do limite aceito');
        assert_same(null, MatchOperationRules::minute('abc'), 'Minuto textual aceito');
    }
}
