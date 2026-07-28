<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ColorRules;
use App\Services\DateRules;
use App\Services\RegulationRules;
use App\Services\Slugger;
use function Tests\assert_same;
use function Tests\assert_true;

final class ChampionshipTest
{
    public static function run(): void
    {
        assert_same('copa-brasil-de-talentos-2026', Slugger::make('Copa Brasil de Talentos 2026'), 'Slug nao normalizado');
        assert_true(ColorRules::valid('#12aB90'), 'Cor hexadecimal valida rejeitada');
        assert_true(!ColorRules::valid('blue'), 'Cor invalida aceita');
        assert_true(DateRules::validate(['starts_at' => '2026-01-01', 'ends_at' => '2026-02-01']) === [], 'Datas validas rejeitadas');
        assert_true(DateRules::validate(['starts_at' => '2026-03-01', 'ends_at' => '2026-02-01']) !== [], 'Ordem de datas invalida aceita');
        assert_true(DateRules::validate(['registration_starts_at' => '2026-05-20', 'registration_ends_at' => '2026-05-01']) !== [], 'Periodo de inscricao invalido aceito');
        $preset = RegulationRules::preset();
        assert_same(2, $preset['format']['group_count'], 'Preset nao cria dois grupos');
        assert_same(5, $preset['format']['teams_per_group'], 'Preset nao cria cinco equipes por grupo');
        assert_same([], RegulationRules::validate($preset), 'Preset inicial invalido');
        $invalid = $preset;
        $invalid['format']['qualified_per_group'] = 6;
        assert_true(RegulationRules::validate($invalid) !== [], 'Classificados acima do grupo foram aceitos');
        $invalid = $preset;
        $invalid['points']['wo_winner_goals'] = -1;
        assert_true(RegulationRules::validate($invalid) !== [], 'W.O. invalido aceito');
        $invalid = $preset;
        $invalid['match']['extra_time_enabled'] = 1;
        $invalid['match']['extra_time_minutes'] = 0;
        assert_true(RegulationRules::validate($invalid) !== [], 'Prorrogacao sem duracao aceita');
        $invalid = $preset;
        $invalid['tiebreakers'][1]['priority'] = 1;
        assert_true(RegulationRules::validate($invalid) !== [], 'Prioridade de desempate duplicada aceita');
        $invalid = $preset;
        $invalid['tiebreakers'][1]['criterion'] = 'wins';
        assert_true(RegulationRules::validate($invalid) !== [], 'Criterio de desempate duplicado aceito');
    }
}
