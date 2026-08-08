<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RegulationRules;
use App\Services\StandingsCalculator;
use function Tests\assert_same;
use function Tests\assert_true;

final class CopaBrasilTalentosTest
{
    public static function run(): void
    {
        $rules = RegulationRules::preset();
        $rules['tiebreakers'] = [
            ['criterion' => 'head_to_head', 'priority' => 1, 'enabled' => 1],
            ['criterion' => 'wins', 'priority' => 2, 'enabled' => 1],
            ['criterion' => 'goal_difference', 'priority' => 3, 'enabled' => 1],
            ['criterion' => 'goals_conceded', 'priority' => 4, 'enabled' => 1],
        ];
        assert_same([], RegulationRules::validate($rules), 'Regra de gols sofridos deveria ser aceita');

        $teams = [
            ['team_id' => 1, 'team_name' => 'A'],
            ['team_id' => 2, 'team_name' => 'B'],
            ['team_id' => 3, 'team_name' => 'C'],
            ['team_id' => 4, 'team_name' => 'D'],
        ];
        $matches = [
            ['home_team_id' => 1, 'away_team_id' => 3, 'home_score' => 0, 'away_score' => 0],
            ['home_team_id' => 1, 'away_team_id' => 4, 'home_score' => 1, 'away_score' => 0],
            ['home_team_id' => 2, 'away_team_id' => 3, 'home_score' => 1, 'away_score' => 1],
            ['home_team_id' => 2, 'away_team_id' => 4, 'home_score' => 1, 'away_score' => 0],
        ];
        $standings = (new StandingsCalculator())->calculate($teams, $matches, [
            'points_win' => 3,
            'points_draw' => 1,
            'points_loss' => 0,
            'tiebreakers' => [['criterion' => 'goals_conceded']],
        ], 2);
        assert_same(1, $standings[0]['team_id'], 'Menor número de gols sofridos deveria desempatar');
        assert_same(2, $standings[1]['team_id'], 'Segundo time do teste deveria ficar em segundo');
        assert_true($standings[0]['goals_against'] < $standings[1]['goals_against'], 'Gols sofridos não calculados');
    }
}
