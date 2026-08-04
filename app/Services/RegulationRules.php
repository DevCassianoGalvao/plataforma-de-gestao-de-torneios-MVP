<?php
declare(strict_types=1);

namespace App\Services;

final class RegulationRules
{
    public const CRITERIA = ['wins', 'goal_difference', 'goals_scored', 'head_to_head', 'fewer_cards', 'administrative_decision', 'draw_lots'];

    public static function preset(): array
    {
        return [
            'format' => ['group_count' => 2, 'teams_per_group' => 5, 'qualified_per_group' => 4, 'group_rounds' => 'single', 'home_and_away' => 0, 'knockout_starts_at' => 'quarterfinals', 'third_place_match' => 0, 'final_format' => 'single_match'],
            'points' => ['points_win' => 3, 'points_draw' => 1, 'points_loss' => 0, 'wo_winner_goals' => 3, 'wo_loser_goals' => 0],
            'discipline' => ['yellow_cards_for_suspension' => 3, 'yellow_suspension_matches' => 1, 'red_card_automatic_suspension' => 1, 'red_card_suspension_matches' => 1, 'reset_cards_enabled' => 0, 'reset_cards_stage' => ''],
            'match' => ['regular_time_minutes' => 40, 'halftime_minutes' => 10, 'substitutions_allowed' => 5, 'substitution_windows' => 3, 'extra_time_enabled' => 0, 'extra_time_minutes' => 10, 'penalty_shootout_enabled' => 1, 'direct_penalties' => 0],
            'roster' => ['minimum_roster_size' => 1, 'maximum_roster_size' => 25, 'minimum_goalkeepers' => 1, 'allow_multiple_team_registration' => 0, 'required_document_type_ids' => []],
            'advanced' => ['maximum_staff_members' => 0, 'maximum_teams' => 0, 'allow_registration_after_start' => 1, 'registration_requires_approval' => 1, 'require_complete_documents' => 1, 'require_minor_authorization' => 1, 'roster_change_limit' => 0, 'roster_change_deadline' => null, 'roster_change_phase_limit' => null, 'transfers_enabled' => 1, 'transfers_blocked' => 0, 'block_athlete_played_other_team' => 0, 'allow_administrative_exception' => 0, 'exception_reason_required' => 1, 'abandoned_match_rule' => 'administrative_decision', 'cancelled_match_rule' => 'administrative_decision', 'postponed_match_rule' => 'reschedule'],
            'eligibility_rules' => [],
            'tiebreakers' => array_map(static fn (string $criterion, int $index): array => ['criterion' => $criterion, 'priority' => $index + 1, 'enabled' => 1], self::CRITERIA, array_keys(self::CRITERIA)),
        ];
    }

    public static function validate(array $data): array
    {
        $errors = [];
        $format = $data['format'] ?? [];
        $points = $data['points'] ?? [];
        $discipline = $data['discipline'] ?? [];
        $match = $data['match'] ?? [];
        $roster = $data['roster'] ?? [];
        $integerRules = [
            [$format, 'group_count', 1, 'A quantidade de grupos deve ser maior que zero.'],
            [$format, 'teams_per_group', 1, 'A quantidade de equipes por grupo deve ser maior que zero.'],
            [$format, 'qualified_per_group', 1, 'A quantidade de classificados deve ser maior que zero.'],
            [$points, 'points_win', 0, 'Pontos por vitoria invalidos.'],
            [$points, 'points_draw', 0, 'Pontos por empate invalidos.'],
            [$points, 'points_loss', 0, 'Pontos por derrota invalidos.'],
            [$points, 'wo_winner_goals', 0, 'Gols do vencedor por W.O. invalidos.'],
            [$points, 'wo_loser_goals', 0, 'Gols do perdedor por W.O. invalidos.'],
            [$discipline, 'yellow_cards_for_suspension', 1, 'Limite de amarelos invalido.'],
            [$discipline, 'yellow_suspension_matches', 1, 'Duracao da suspensao por amarelos invalida.'],
            [$discipline, 'red_card_suspension_matches', 1, 'Duracao da suspensao por vermelho invalida.'],
            [$match, 'regular_time_minutes', 1, 'Duracao da partida invalida.'],
            [$match, 'halftime_minutes', 0, 'Duracao do intervalo invalida.'],
            [$match, 'substitutions_allowed', 0, 'Quantidade de substituicoes invalida.'],
            [$match, 'substitution_windows', 0, 'Janelas de substituicao invalidas.'],
            [$match, 'extra_time_minutes', 0, 'Duracao da prorrogacao invalida.'],
        ];
        if ($roster !== []) {
            $integerRules = array_merge($integerRules, [
                [$roster, 'minimum_roster_size', 0, 'Tamanho minimo do elenco invalido.'],
                [$roster, 'maximum_roster_size', 1, 'Tamanho maximo do elenco invalido.'],
                [$roster, 'minimum_goalkeepers', 0, 'Quantidade minima de goleiros invalida.'],
            ]);
            if ((int) ($roster['minimum_roster_size'] ?? 0) > (int) ($roster['maximum_roster_size'] ?? 0)) {
                $errors[] = 'O minimo do elenco nao pode superar o maximo.';
            }
            $documentTypes = array_values(array_unique(array_filter(array_map('intval', (array) ($roster['required_document_type_ids'] ?? [])))));
            if (count($documentTypes) !== count((array) ($roster['required_document_type_ids'] ?? []))) {
                $errors[] = 'Tipos de documento obrigatorio invalidos.';
            }
        }
        $advanced = $data['advanced'] ?? [];
        foreach (['maximum_staff_members','maximum_teams','roster_change_limit'] as $key) if ($advanced !== [] && (int) ($advanced[$key] ?? 0) < 0) $errors[] = 'Configuracao avancada invalida.';
        foreach ((array) ($data['eligibility_rules'] ?? []) as $rule) {
            if ((int) ($rule['source_phase_id'] ?? 0) <= 0 || (int) ($rule['destination_phase_id'] ?? 0) <= 0 || (int) ($rule['source_phase_id'] ?? 0) === (int) ($rule['destination_phase_id'] ?? 0)) $errors[] = 'Regra de elegibilidade precisa ter fases diferentes.';
            if ((int) ($rule['minimum_participations'] ?? 0) < 0 || !in_array($rule['participation_type'] ?? '', ['listed','played','starter'], true)) $errors[] = 'Regra de participacao invalida.';
        }
        foreach ($integerRules as [$section, $key, $minimum, $message]) {
            if ((int) ($section[$key] ?? -1) < $minimum) {
                $errors[] = $message;
            }
        }
        if ((int) ($format['qualified_per_group'] ?? 0) > (int) ($format['teams_per_group'] ?? 0)) {
            $errors[] = 'Classificados por grupo nao podem superar equipes por grupo.';
        }
        if (!in_array($format['group_rounds'] ?? '', ['single', 'double'], true)) {
            $errors[] = 'Formato de grupos invalido.';
        }
        if (!in_array($format['knockout_starts_at'] ?? '', ['quarterfinals', 'semifinals', 'final'], true)) {
            $errors[] = 'Inicio do mata-mata invalido.';
        }
        if (!in_array($format['final_format'] ?? '', ['single_match', 'two_legs'], true)) {
            $errors[] = 'Formato da final invalido.';
        }
        $priorities = [];
        $criteria = [];
        foreach ((array) ($data['tiebreakers'] ?? []) as $item) {
            if (!in_array($item['criterion'] ?? '', self::CRITERIA, true)) {
                $errors[] = 'Criterio de desempate invalido.';
            }
            if (in_array($item['criterion'] ?? '', $criteria, true)) {
                $errors[] = 'Os criterios de desempate nao podem se repetir.';
            }
            $criteria[] = $item['criterion'] ?? '';
            if (($item['enabled'] ?? 0) && (int) ($item['priority'] ?? 0) < 1) {
                $errors[] = 'Prioridade de desempate invalida.';
            }
            if (($item['enabled'] ?? 0) && in_array((int) $item['priority'], $priorities, true)) {
                $errors[] = 'As prioridades de desempate nao podem se repetir.';
            }
            if (($item['enabled'] ?? 0)) {
                $priorities[] = (int) $item['priority'];
            }
        }
        if ($priorities === []) {
            $errors[] = 'Ative pelo menos um criterio de desempate.';
        }
        if (!empty($match['extra_time_enabled']) && (int) ($match['extra_time_minutes'] ?? 0) < 1) {
            $errors[] = 'A prorrogacao precisa ter duracao maior que zero.';
        }
        return array_values(array_unique($errors));
    }
}
