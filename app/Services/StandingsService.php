<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\StandingsRepository;

final class StandingsService
{
    public function __construct(private readonly StandingsRepository $standings, private readonly AuditService $audit)
    {
    }

    public function recalculate(array $phase, int $userId): array
    {
        $regulation = $this->standings->regulation((int) $phase['championship_id']);
        $all = [];
        $this->standings->begin();
        try {
            foreach ($this->standings->groups((int) $phase['id']) as $group) {
                $rows = $this->calculateGroup($phase, $group, $regulation);
                $hash = hash('sha256', json_encode([$group['id'], $regulation, $rows, $this->standings->homologatedMatches((int) $group['id'])], JSON_THROW_ON_ERROR));
                $this->standings->replaceGroupStandings((int) $group['id'], $rows, $userId, $hash, $phase);
                $all[(int) $group['id']] = $rows;
            }
            $this->standings->commit();
        } catch (\Throwable $exception) {
            $this->standings->rollBack();
            throw $exception;
        }
        $this->audit->record('standings.recalculated', $userId, 'competition_phase', (int) $phase['id'], ['groups' => count($all)], null);
        return ['ok' => true, 'errors' => [], 'standings' => $all];
    }

    public function groupStandings(int $groupId): array
    {
        return $this->standings->standings($groupId);
    }

    public function generateKnockout(array $phase, int $userId): array
    {
        $this->recalculate($phase, $userId);
        $sourceGroups = $this->standings->groups((int) $phase['id']);
        $regulation = $this->standings->regulation((int) $phase['championship_id']);
        $qualified = [];
        foreach ($sourceGroups as $group) {
            $qualified[$group['code']] = array_slice($this->standings->standings((int) $group['id']), 0, (int) $regulation['qualified_per_group']);
        }
        if (count($qualified) < 2 || count($qualified[array_key_first($qualified)] ?? []) < 4 || count($qualified[array_key_last($qualified)] ?? []) < 4) return $this->fail('Sao necessarios dois grupos com quatro classificados.');
        $pairs = $this->standings->knockoutPairings((int) $regulation['id'], 'quarterfinals');
        if ($pairs === []) return $this->fail('Configure os cruzamentos do mata-mata no regulamento.');
        $knockout = $this->standings->ensureKnockoutPhase($phase, $userId);
        $this->standings->begin();
        try {
            $roundIds = [];
            foreach (StandingsRules::STAGES as $index => $stage) $roundIds[$stage] = $this->standings->ensureKnockoutRound($knockout, $stage, $index + 1, $userId);
            foreach ($pairs as $pair) {
                $homeSource = (string) $pair['home_source']; $awaySource = (string) $pair['away_source']; $index = (int) $pair['tie_number'] - 1;
                $home = $this->qualifiedTeam($qualified, $homeSource);
                $away = $this->qualifiedTeam($qualified, $awaySource);
                $tieId = $this->standings->upsertTie($roundIds['quarterfinals'], $index + 1, $homeSource, $awaySource, $home, $away);
                $this->attachMatchIfReady($tieId, $knockout, $home, $away, $index + 1, $userId);
            }
            $this->upsertProgressionTies($roundIds, $knockout, $regulation, $userId);
            $this->standings->commit();
        } catch (\Throwable $exception) {
            $this->standings->rollBack();
            throw $exception;
        }
        $this->audit->record('knockout.generated', $userId, 'competition_phase', (int) $knockout['id'], ['source_phase_id' => $phase['id']], null);
        return ['ok' => true, 'errors' => [], 'phase' => $knockout, 'rounds' => $this->bracket((int) $knockout['id'])];
    }

    public function processKnockoutMatch(array $match, int $userId): array
    {
        if (($match['status'] ?? '') !== 'homologated') return $this->fail('Somente partida homologada pode avançar no mata-mata.');
        $tie = $this->standings->knockoutMatch((int) $match['id']);
        if (!$tie) return $this->fail('Partida nao pertence a uma chave de mata-mata.');
        if (($tie['status'] ?? '') === 'finished' && !empty($tie['winner_team_id'])) {
            return ['ok' => true, 'errors' => [], 'winner_team_id' => (int) $tie['winner_team_id'], 'runner_up_team_id' => (int) ($tie['loser_team_id'] ?? 0), 'decided_by' => $tie['decided_by']];
        }
        $row = $this->standings->homologatedMatch((int) $match['id']);
        if (!$row) return $this->fail('Resultado homologado nao encontrado.');
        $homeScore = $row['administrative_home_score'] !== null ? (int) $row['administrative_home_score'] : (int) $row['event_home_score'];
        $awayScore = $row['administrative_away_score'] !== null ? (int) $row['administrative_away_score'] : (int) $row['event_away_score'];
        $decidedBy = $row['administrative_home_score'] !== null ? 'administrative' : 'regular_time';
        if ($homeScore === $awayScore) {
            if ((int) $row['home_penalties'] === (int) $row['away_penalties']) return $this->fail('Empate sem prorrogação ou pênaltis não define classificado.');
            $homeWins = (int) $row['home_penalties'] > (int) $row['away_penalties'];
            $decidedBy = 'penalties';
        } else {
            $homeWins = $homeScore > $awayScore;
        }
        $winner = $homeWins ? (int) $row['home_team_id'] : (int) $row['away_team_id'];
        $loser = $homeWins ? (int) $row['away_team_id'] : (int) $row['home_team_id'];
        $this->standings->updateTieDecision((int) $tie['id'], $winner, $loser, $decidedBy);
        $this->advanceAfterTie($tie, $winner, $loser, $userId);
        $this->audit->record('knockout.match_advanced', $userId, 'match', (int) $match['id'], ['winner_team_id' => $winner, 'decided_by' => $decidedBy], null);
        return ['ok' => true, 'errors' => [], 'winner_team_id' => $winner, 'runner_up_team_id' => $loser, 'decided_by' => $decidedBy];
    }

    public function bracket(int $phaseId): array
    {
        $result = [];
        foreach ($this->standings->knockoutRounds($phaseId) as $round) $result[$round['stage']] = $this->standings->ties((int) $round['id']);
        return $result;
    }

    public function result(int $championshipId, int $phaseId): ?array
    {
        return $this->standings->result($championshipId, $phaseId);
    }

    private function calculateGroup(array $phase, array $group, array $regulation): array
    {
        $rows = [];
        foreach ($this->standings->groupTeams((int) $group['id']) as $team) $rows[(int) $team['team_id']] = ['team_id' => (int) $team['team_id'], 'matches_played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'points' => 0, 'win_percentage' => 0, 'position' => 0, 'situation' => 'pending', 'separated_by' => null, 'discipline_cards' => $this->standings->disciplineCards((int) $phase['championship_id'], (int) $team['team_id']), 'administrative_score' => $this->standings->administrativeScore((int) $group['id'], (int) $team['team_id'])];
        $matches = array_map(static fn (array $match): array => [
            'home_team_id' => (int) $match['home_team_id'], 'away_team_id' => (int) $match['away_team_id'],
            'home_score' => $match['administrative_home_score'] !== null ? (int) $match['administrative_home_score'] : (int) $match['event_home_score'],
            'away_score' => $match['administrative_away_score'] !== null ? (int) $match['administrative_away_score'] : (int) $match['event_away_score'],
        ], $this->standings->homologatedMatches((int) $group['id']));
        return (new StandingsCalculator())->calculate(array_values($rows), $matches, $regulation, (int) $regulation['qualified_per_group']);
    }

    private function sortRows(array $rows, int $groupId, array $regulation): array
    {
        $criteria = array_map(static fn (array $item): string => (string) $item['criterion'], $regulation['tiebreakers']);
        usort($rows, function (array $a, array $b) use ($criteria, $groupId, $rows): int {
            if ($a['points'] !== $b['points']) return $b['points'] <=> $a['points'];
            foreach ($criteria as $criterion) {
                $compare = $this->compareCriterion($a, $b, $criterion, $groupId, $rows);
                if ($compare !== 0) return $compare;
            }
            return (int) $a['team_id'] <=> (int) $b['team_id'];
        });
        for ($i = 1; $i < count($rows); $i++) if ($rows[$i]['points'] === $rows[$i - 1]['points']) $rows[$i]['separated_by'] = $this->separatingCriterion($rows[$i - 1], $rows[$i], $criteria, $groupId, $rows);
        return $rows;
    }

    private function compareCriterion(array $a, array $b, string $criterion, int $groupId, array $cluster): int
    {
        return match ($criterion) {
            'wins' => $b['wins'] <=> $a['wins'],
            'goal_difference' => $b['goal_difference'] <=> $a['goal_difference'],
            'goals_scored' => $b['goals_for'] <=> $a['goals_for'],
            'head_to_head' => $this->headToHead($a['team_id'], $b['team_id'], $groupId, $cluster),
            'fewer_cards' => $a['discipline_cards'] <=> $b['discipline_cards'],
            'administrative_decision' => $b['administrative_score'] <=> $a['administrative_score'],
            'draw_lots' => (int) $a['team_id'] <=> (int) $b['team_id'],
            default => 0,
        };
    }

    private function separatingCriterion(array $a, array $b, array $criteria, int $groupId, array $cluster): ?string
    {
        foreach ($criteria as $criterion) if ($this->compareCriterion($a, $b, $criterion, $groupId, $cluster) !== 0) return $criterion;
        return 'draw_lots';
    }

    private function headToHead(int $first, int $second, int $groupId, array $cluster): int
    {
        $teamIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['team_id'], $cluster)));
        if (!in_array($first, $teamIds, true)) $teamIds[] = $first;
        if (!in_array($second, $teamIds, true)) $teamIds[] = $second;
        $score = array_fill_keys($teamIds, ['points' => 0, 'gd' => 0, 'gf' => 0]);
        foreach ($this->standings->directMatches($groupId, $teamIds) as $match) {
            $home = $match['administrative_home_score'] !== null ? (int) $match['administrative_home_score'] : (int) $match['event_home_score'];
            $away = $match['administrative_away_score'] !== null ? (int) $match['administrative_away_score'] : (int) $match['event_away_score'];
            $h = (int) $match['home_team_id']; $a = (int) $match['away_team_id'];
            if (!isset($score[$h], $score[$a])) continue;
            $score[$h]['gf'] += $home; $score[$h]['gd'] += $home - $away; $score[$a]['gf'] += $away; $score[$a]['gd'] += $away - $home;
            if ($home > $away) $score[$h]['points'] += 3; elseif ($away > $home) $score[$a]['points'] += 3; else { $score[$h]['points']++; $score[$a]['points']++; }
        }
        foreach (['points', 'gd', 'gf'] as $key) if ($score[$first][$key] !== $score[$second][$key]) return $score[$second][$key] <=> $score[$first][$key];
        return 0;
    }

    private function qualifiedTeam(array $qualified, string $source): ?int
    {
        $code = substr($source, 0, -1); $position = (int) substr($source, -1);
        return isset($qualified[$code][$position - 1]) ? (int) $qualified[$code][$position - 1]['team_id'] : null;
    }

    private function upsertProgressionTies(array $roundIds, array $phase, array $regulation, int $userId): void
    {
        foreach (['semifinals', 'final'] as $stage) {
            foreach ($this->standings->knockoutPairings((int) $regulation['id'], $stage) as $pair) {
                $this->standings->upsertTie($roundIds[$stage], (int) $pair['tie_number'], (string) $pair['home_source'], (string) $pair['away_source'], null, null);
            }
        }
    }

    private function advanceAfterTie(array $tie, int $winner, int $loser, int $userId): void
    {
        $phase = $this->standings->phase((int) $tie['phase_id']);
        if (!$phase) return;
        $stage = (string) $tie['stage'];
        if ($stage === 'final') {
            $this->standings->saveResult((int) $tie['championship_id'], (int) $tie['phase_id'], $winner, $loser, $userId);
            return;
        }
        $source = match ($stage) { 'quarterfinals' => 'QF' . $tie['tie_number'], 'semifinals' => 'SF' . $tie['tie_number'], default => '' };
        if ($source === '') return;
        foreach ($this->standings->knockoutRounds((int) $tie['phase_id']) as $round) {
            foreach ($this->standings->ties((int) $round['id']) as $next) {
                if (!in_array($source, [(string) $next['home_source'], (string) $next['away_source']], true)) continue;
                $homeTie = $this->sourceTie((int) $tie['phase_id'], (string) $next['home_source']);
                $awayTie = $this->sourceTie((int) $tie['phase_id'], (string) $next['away_source']);
                $home = $homeTie['winner_team_id'] ?? null;
                $away = $awayTie['winner_team_id'] ?? null;
                $nextId = $this->standings->upsertTie((int) $round['id'], (int) $next['tie_number'], (string) $next['home_source'], (string) $next['away_source'], $home ? (int) $home : null, $away ? (int) $away : null);
                $roundNumber = $round['stage'] === 'semifinals' ? 5 + (int) $next['tie_number'] : 7;
                $this->attachMatchIfReady($nextId, $phase, $home ? (int) $home : null, $away ? (int) $away : null, $roundNumber, $userId);
            }
        }
    }

    private function attachMatchIfReady(int $tieId, array $phase, ?int $home, ?int $away, int $roundNumber, int $userId): void
    {
        if (!$home || !$away) return;
        $tie = $this->standings->tie($tieId);
        if (!$tie || $tie['match_id']) return;
        $groups = $this->standings->groups((int) $phase['id']); $group = $groups[0] ?? null; if (!$group) return;
        $matchId = $this->standings->createKnockoutMatch($phase, (int) $group['id'], $home, $away, $roundNumber, $userId);
        $this->standings->attachTieMatch($tieId, $matchId);
    }

    private function findTieByStageNumber(int $phaseId, string $stage, int $number): ?array
    {
        foreach ($this->standings->knockoutRounds($phaseId) as $round) if ($round['stage'] === $stage) return $this->standings->ties((int) $round['id'])[$number - 1] ?? null;
        return null;
    }

    private function sourceTie(int $phaseId, string $source): ?array
    {
        if (preg_match('/^(QF|SF)([1-9][0-9]*)$/', $source, $matches) !== 1) return null;
        return $this->findTieByStageNumber($phaseId, $matches[1] === 'QF' ? 'quarterfinals' : 'semifinals', (int) $matches[2]);
    }

    private function win(array &$winner, array &$loser, array $regulation): void
    {
        $winner['wins']++; $loser['losses']++; $winner['points'] += (int) $regulation['points_win']; $loser['points'] += (int) $regulation['points_loss'];
    }

    private function draw(array &$first, array &$second, array $regulation): void
    {
        $first['draws']++; $second['draws']++; $first['points'] += (int) $regulation['points_draw']; $second['points'] += (int) $regulation['points_draw'];
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'errors' => [$message]];
    }
}
