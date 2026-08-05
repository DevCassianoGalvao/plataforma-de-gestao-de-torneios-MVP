<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\PublicPortalRepository;

/**
 * Calculates an ephemeral public projection with the same standings engine
 * used by the internal simulator. It never writes to the database.
 */
final class PublicStandingsSimulationService
{
    public function __construct(
        private readonly PublicPortalRepository $portal,
        private readonly StandingsCalculator $calculator,
    ) {
    }

    public function viewData(int $championshipId): array
    {
        $data = $this->portal->simulationData($championshipId);
        return [
            'points' => $data['points'],
            'matches' => array_values($data['matches']),
        ];
    }

    public function project(int $championshipId, array $rawScores = []): array
    {
        $data = $this->portal->simulationData($championshipId);
        $scores = $this->validatedScores($data['matches'], $rawScores);
        if ($scores['errors'] !== []) return ['ok' => false, 'errors' => $scores['errors']];

        $groups = [];
        foreach ($data['groups'] as $group) {
            $groupId = (int) $group['id'];
            $teams = array_map(static fn (array $team): array => [
                'team_id' => (int) $team['team_id'],
                'team_name' => $team['team_name'],
                'team_short_name' => $team['team_short_name'],
                'slug' => $team['slug'],
                'shield_path' => $team['shield_path'],
                'discipline_cards' => (int) $team['discipline_cards'],
                'administrative_score' => (int) $team['administrative_score'],
            ], $data['teams'][$groupId] ?? []);
            if ($teams === []) continue;

            $officialMatches = [];
            $simulatedMatches = [];
            foreach ($data['matches'] as $match) {
                if ((int) $match['group_id'] !== $groupId) continue;
                $payload = [
                    'home_team_id' => (int) $match['home_team_id'],
                    'away_team_id' => (int) $match['away_team_id'],
                    'home_score' => (int) $match['home_score'],
                    'away_score' => (int) $match['away_score'],
                ];
                if ((string) $match['status'] === 'homologated') $officialMatches[] = $payload;
                $matchId = (int) $match['id'];
                if (isset($scores['values'][$matchId])) {
                    $simulatedMatches[] = array_merge($payload, $scores['values'][$matchId]);
                } elseif ((string) $match['status'] === 'homologated') {
                    $simulatedMatches[] = $payload;
                }
            }

            $qualified = (int) ($group['qualified_limit'] ?: ($data['regulation']['qualified_per_group'] ?? 0));
            $official = $this->calculator->calculate($teams, $officialMatches, $data['regulation'], $qualified);
            $simulated = $this->calculator->calculate($teams, $simulatedMatches, $data['regulation'], $qualified);
            $officialPositions = [];
            foreach ($official as $row) $officialPositions[(int) $row['team_id']] = (int) $row['position'];
            foreach ($simulated as &$row) {
                $teamId = (int) $row['team_id'];
                $row['official_position'] = $officialPositions[$teamId] ?? (int) $row['position'];
                $row['position_change'] = $row['official_position'] - (int) $row['position'];
            }
            unset($row);

            $groups[] = [
                'id' => $groupId,
                'name' => (string) $group['name'],
                'qualified_limit' => $qualified,
                'official' => $official,
                'simulated' => $simulated,
            ];
        }

        return [
            'ok' => true,
            'changed' => $scores['changed'],
            'groups' => $groups,
            'criteria' => array_map(static fn (array $criterion): string => (string) $criterion['criterion'], $data['regulation']['tiebreakers'] ?? []),
        ];
    }

    /** @return array{values: array<int, array{home_score:int, away_score:int}>, errors: array<int, string>, changed: bool} */
    private function validatedScores(array $matches, array $rawScores): array
    {
        if (count($rawScores) > 300) return ['values' => [], 'errors' => ['Envie no maximo 300 partidas por simulacao.'], 'changed' => false];
        $allowed = [];
        foreach ($matches as $match) {
            if (in_array((string) $match['status'], ['homologated', 'scheduled', 'confirmed', 'postponed'], true)) {
                $allowed[(int) $match['id']] = [
                    'home_score' => (string) $match['home_score'],
                    'away_score' => (string) $match['away_score'],
                    'official' => (string) $match['status'] === 'homologated',
                ];
            }
        }
        $values = [];
        $changed = false;
        foreach ($rawScores as $matchId => $score) {
            $id = filter_var($matchId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id || !isset($allowed[(int) $id])) return ['values' => [], 'errors' => ['A partida informada nao esta disponivel para simulacao.'], 'changed' => false];
            if (!is_array($score)) return ['values' => [], 'errors' => ['Informe um placar valido.'], 'changed' => false];
            $homeRaw = $score['home'] ?? null;
            $awayRaw = $score['away'] ?? null;
            if (($homeRaw === null || $homeRaw === '') && ($awayRaw === null || $awayRaw === '')) continue;
            $home = $this->scoreValue($homeRaw);
            $away = $this->scoreValue($awayRaw);
            if ($home === null || $away === null) return ['values' => [], 'errors' => ['Informe dois numeros inteiros entre 0 e 99 para cada partida.'], 'changed' => false];
            $values[(int) $id] = ['home_score' => $home, 'away_score' => $away];
            if (!$allowed[(int) $id]['official'] || (string) $home !== $allowed[(int) $id]['home_score'] || (string) $away !== $allowed[(int) $id]['away_score']) $changed = true;
        }
        return ['values' => $values, 'errors' => [], 'changed' => $changed];
    }

    private function scoreValue(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        if (is_int($value)) return $value >= 0 && $value <= 99 ? $value : null;
        if (!is_string($value) || !preg_match('/^\d{1,2}$/', $value)) return null;
        $score = (int) $value;
        return $score >= 0 && $score <= 99 ? $score : null;
    }
}
