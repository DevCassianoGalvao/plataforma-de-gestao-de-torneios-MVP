<?php
declare(strict_types=1);

namespace App\Services;

final class RoundRobinGenerator
{
    public static function generate(array $teamIds, bool $returnLeg = false): array
    {
        $teams = array_values(array_unique(array_map('intval', $teamIds)));
        if (count($teams) < 2) return [];
        if (count($teams) % 2 !== 0) $teams[] = null;
        $roundCount = count($teams) - 1;
        $rounds = [];
        for ($round = 0; $round < $roundCount; $round++) {
            $matches = [];
            $size = count($teams);
            for ($index = 0; $index < $size / 2; $index++) {
                $home = $teams[$index];
                $away = $teams[$size - 1 - $index];
                if ($home === null || $away === null) continue;
                if ($round % 2 === 1) [$home, $away] = [$away, $home];
                $matches[] = ['home_team_id' => $home, 'away_team_id' => $away, 'round_number' => $round + 1, 'leg_number' => 1];
            }
            $rounds[] = $matches;
            $fixed = array_shift($teams);
            $last = array_pop($teams);
            array_unshift($teams, $fixed);
            array_splice($teams, 1, 0, [$last]);
        }
        if (!$returnLeg) return $rounds;
        $firstLeg = $rounds;
        foreach ($firstLeg as $matches) {
            $returnMatches = [];
            foreach ($matches as $match) {
                $returnMatches[] = ['home_team_id' => $match['away_team_id'], 'away_team_id' => $match['home_team_id'], 'round_number' => $match['round_number'] + $roundCount, 'leg_number' => 2];
            }
            $rounds[] = $returnMatches;
        }
        return $rounds;
    }
}
