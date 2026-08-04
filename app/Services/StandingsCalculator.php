<?php
declare(strict_types=1);

namespace App\Services;

final class StandingsCalculator
{
    public function calculate(array $teams, array $matches, array $regulation, int $qualified): array
    {
        $rows = [];
        foreach ($teams as $team) {
            $id = (int) $team['team_id'];
            $rows[$id] = array_merge(['team_id' => $id, 'matches_played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0, 'win_percentage' => 0, 'position' => 0, 'situation' => 'pending', 'separated_by' => null, 'discipline_cards' => 0, 'administrative_score' => 0], $team);
        }
        foreach ($matches as $match) {
            $homeId = (int) $match['home_team_id']; $awayId = (int) $match['away_team_id'];
            if (!isset($rows[$homeId], $rows[$awayId])) continue;
            $home = (int) $match['home_score']; $away = (int) $match['away_score'];
            $rows[$homeId]['matches_played']++; $rows[$awayId]['matches_played']++;
            $rows[$homeId]['goals_for'] += $home; $rows[$homeId]['goals_against'] += $away;
            $rows[$awayId]['goals_for'] += $away; $rows[$awayId]['goals_against'] += $home;
            if ($home > $away) $this->win($rows[$homeId], $rows[$awayId], $regulation);
            elseif ($away > $home) $this->win($rows[$awayId], $rows[$homeId], $regulation);
            else $this->draw($rows[$homeId], $rows[$awayId], $regulation);
        }
        foreach ($rows as &$row) {
            $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
            $row['win_percentage'] = $row['matches_played'] ? round(($row['wins'] / $row['matches_played']) * 100, 2) : 0;
        }
        unset($row);
        $criteria = array_map(static fn (array $item): string => (string) $item['criterion'], $regulation['tiebreakers'] ?? []);
        $matches = array_values($matches);
        $rows = array_values($rows);
        usort($rows, function (array $first, array $second) use ($criteria, $matches, $rows): int {
            if ($first['points'] !== $second['points']) return $second['points'] <=> $first['points'];
            foreach ($criteria as $criterion) {
                $compare = $this->compare($first, $second, $criterion, $matches, $rows);
                if ($compare !== 0) return $compare;
            }
            return (int) $first['team_id'] <=> (int) $second['team_id'];
        });
        foreach ($rows as $index => &$row) {
            $row['position'] = $index + 1;
            $row['situation'] = $index < $qualified ? 'qualified' : 'eliminated';
            if ($index > 0 && $row['points'] === $rows[$index - 1]['points']) $row['separated_by'] = $this->separatedBy($rows[$index - 1], $row, $criteria, $matches, $rows);
        }
        unset($row);
        return $rows;
    }

    private function win(array &$winner, array &$loser, array $regulation): void { $winner['wins']++; $loser['losses']++; $winner['points'] += (int) $regulation['points_win']; $loser['points'] += (int) $regulation['points_loss']; }
    private function draw(array &$first, array &$second, array $regulation): void { $first['draws']++; $second['draws']++; $first['points'] += (int) $regulation['points_draw']; $second['points'] += (int) $regulation['points_draw']; }
    private function compare(array $a, array $b, string $criterion, array $matches, array $cluster): int
    {
        return match ($criterion) {
            'wins' => $b['wins'] <=> $a['wins'], 'goal_difference' => $b['goal_difference'] <=> $a['goal_difference'], 'goals_scored' => $b['goals_for'] <=> $a['goals_for'],
            'head_to_head' => $this->headToHead((int) $a['team_id'], (int) $b['team_id'], $matches, $cluster), 'fewer_cards' => $a['discipline_cards'] <=> $b['discipline_cards'],
            'administrative_decision' => $b['administrative_score'] <=> $a['administrative_score'], 'draw_lots' => (int) $a['team_id'] <=> (int) $b['team_id'], default => 0,
        };
    }
    private function separatedBy(array $a, array $b, array $criteria, array $matches, array $cluster): string
    { foreach ($criteria as $criterion) if ($this->compare($a, $b, $criterion, $matches, $cluster) !== 0) return $criterion; return 'draw_lots'; }
    private function headToHead(int $first, int $second, array $matches, array $cluster): int
    {
        $ids = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['team_id'], $cluster)));
        $score = array_fill_keys($ids, ['points' => 0, 'gd' => 0, 'gf' => 0]);
        foreach ($matches as $match) {
            $home = (int) $match['home_team_id']; $away = (int) $match['away_team_id'];
            if (!isset($score[$home], $score[$away])) continue;
            $homeScore = (int) $match['home_score']; $awayScore = (int) $match['away_score'];
            $score[$home]['gf'] += $homeScore; $score[$home]['gd'] += $homeScore - $awayScore;
            $score[$away]['gf'] += $awayScore; $score[$away]['gd'] += $awayScore - $homeScore;
            if ($homeScore > $awayScore) $score[$home]['points'] += 3; elseif ($awayScore > $homeScore) $score[$away]['points'] += 3; else { $score[$home]['points']++; $score[$away]['points']++; }
        }
        foreach (['points', 'gd', 'gf'] as $key) if ($score[$first][$key] !== $score[$second][$key]) return $score[$second][$key] <=> $score[$first][$key];
        return 0;
    }
}
