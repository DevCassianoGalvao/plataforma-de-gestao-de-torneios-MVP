<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RoundRobinGenerator;
use App\Services\ScheduleRules;
use function Tests\assert_same;
use function Tests\assert_true;

final class ScheduleTest
{
    public static function run(): void
    {
        $single = RoundRobinGenerator::generate([1, 2, 3, 4]);
        assert_same(3, count($single), 'Round-robin par deve criar tres rodadas');
        assert_same(6, count(array_merge(...$single)), 'Round-robin par deve criar seis jogos');
        self::assertPairs($single, 1);

        $odd = RoundRobinGenerator::generate([1, 2, 3, 4, 5]);
        assert_same(5, count($odd), 'Round-robin impar deve criar cinco rodadas');
        assert_same(10, count(array_merge(...$odd)), 'Round-robin impar deve respeitar folgas');
        self::assertPairs($odd, 1);

        $double = RoundRobinGenerator::generate([1, 2, 3, 4], true);
        assert_same(6, count($double), 'Ida e volta deve duplicar rodadas');
        assert_same(12, count(array_merge(...$double)), 'Ida e volta deve criar doze jogos');
        self::assertPairs($double, 2);
        assert_true(!ScheduleRules::canTransitionMatch('scheduled', 'finished'), 'Partida nao deve pular operacao nesta etapa');
        assert_true(ScheduleRules::canTransitionMatch('scheduled', 'postponed'), 'Adiamento valido foi rejeitado');
        assert_true(ScheduleRules::validateSchedule(['period_start' => '2026-09-01', 'period_end' => '2026-09-30', 'allowed_days' => [2], 'start_time' => '18:00', 'venue_ids' => [1]]) === [], 'Agenda valida foi rejeitada');
        assert_true(ScheduleRules::validateSchedule(['period_start' => '2026-09-30', 'period_end' => '2026-09-01', 'allowed_days' => [], 'start_time' => 'x', 'venue_ids' => []]) !== [], 'Agenda invalida foi aceita');
    }

    private static function assertPairs(array $rounds, int $expectedOccurrences): void
    {
        $pairs = [];
        foreach ($rounds as $round) foreach ($round as $match) {
            assert_true((int) $match['home_team_id'] !== (int) $match['away_team_id'], 'Equipe jogou contra si mesma');
            $pair = [min((int) $match['home_team_id'], (int) $match['away_team_id']), max((int) $match['home_team_id'], (int) $match['away_team_id'])];
            $key = implode('-', $pair);
            $pairs[$key] = ($pairs[$key] ?? 0) + 1;
        }
        assert_true($pairs !== [] && count(array_unique($pairs)) === 1 && reset($pairs) === $expectedOccurrences, 'Confronto duplicado ou ausente');
    }
}
