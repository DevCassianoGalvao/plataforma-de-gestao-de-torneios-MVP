<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\StandingsRules;
use function Tests\assert_same;
use function Tests\assert_true;

final class StandingsTest
{
    public static function run(): void
    {
        assert_true(StandingsRules::stage('quarterfinals'), 'Quartas invalidas');
        assert_true(StandingsRules::stage('semifinals'), 'Semifinais invalidas');
        assert_true(StandingsRules::stage('final'), 'Final invalida');
        assert_true(!StandingsRules::stage('group_stage'), 'Fase desconhecida aceita');
        assert_same(['quarterfinals', 'semifinals', 'final'], StandingsRules::STAGES, 'Preset do mata-mata incorreto');
    }
}
