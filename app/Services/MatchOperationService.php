<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LineupRepository;
use App\Repositories\MatchOperationRepository;

final class MatchOperationService
{
    public function __construct(private readonly MatchOperationRepository $operations, private readonly LineupRepository $lineups, private readonly AuditService $audit, private readonly ?DisciplineService $discipline = null, private readonly ?MatchReportService $reports = null, private readonly ?CompetitionProgressService $competition = null)
    {
    }

    public function ensure(array $match, int $userId): array
    {
        return $this->operations->ensure((int) $match['id'], $userId);
    }

    public function payload(array $match, int $userId): array
    {
        $operation = $this->ensure($match, $userId);
        $lineups = [];
        foreach ($this->lineups->listForMatch((int) $match['id']) as $summary) {
            $fullLineup = $this->lineups->find((int) $match['id'], (int) $summary['team_id']);
            $lineups[] = $fullLineup ? array_merge($summary, ['players' => $fullLineup['players'], 'slots' => $this->lineups->formationSlots((int) $summary['tactical_formation_id'])]) : $summary;
        }
        return [
            'operation' => $operation,
            'events' => $this->operations->events((int) $match['id']),
            'substitutions' => $this->operations->substitutions((int) $match['id']),
            'officials' => $this->operations->officials((int) $match['id']),
            'history' => $this->operations->history((int) $operation['id']),
            'score' => $this->operations->score($operation),
            'settings' => $this->operations->matchSettings((int) $match['championship_id']),
            'checklist' => $this->checklist($match, $operation),
            'lineups' => $lineups,
            'players' => $this->playersForMatch($match),
            'staff' => $this->staffForMatch($match),
            'discipline' => $this->discipline ? ['home' => $this->discipline->activeForMatch((int) $match['championship_id'], (int) $match['id'], (int) $match['home_team_id']), 'away' => $this->discipline->activeForMatch((int) $match['championship_id'], (int) $match['id'], (int) $match['away_team_id'])] : ['home' => [], 'away' => []],
        ];
    }

    public function addEvent(array $user, array $match, array $data): array
    {
        $operation = $this->operations->ensure((int) $match['id'], (int) $user['id']);
        if ($operation['status'] !== 'open') return $this->fail('Operacao encerrada; reabertura avancada nao esta disponivel no MVP.');
        $type = trim((string) ($data['event_type'] ?? ''));
        $period = trim((string) ($data['period'] ?? 'regular'));
        if (!MatchOperationRules::eventType($type)) return $this->fail('Tipo de registro invalido.');
        if (!MatchOperationRules::period($period)) return $this->fail('Periodo invalido.');
        $teamId = ($data['team_id'] ?? '') === '' ? null : (int) $data['team_id'];
        $personType = trim((string) ($data['person_type'] ?? 'athlete')) ?: 'athlete';
        $athleteId = ($data['athlete_id'] ?? '') === '' ? null : (int) $data['athlete_id'];
        $staffId = ($data['team_staff_id'] ?? '') === '' ? null : (int) $data['team_staff_id'];
        $relatedAthleteId = ($data['related_athlete_id'] ?? '') === '' ? null : (int) $data['related_athlete_id'];
        if ($teamId !== null && !$this->isMatchTeam($match, $teamId)) return $this->fail('Equipe nao pertence a partida.');
        if (!in_array($personType, ['athlete', 'staff'], true)) return $this->fail('Pessoa disciplinar invalida.');
        if (in_array($type, ['yellow', 'second_yellow', 'red'], true) && (($personType === 'athlete' && !$athleteId) || ($personType === 'staff' && !$staffId))) return $this->fail('Selecione a pessoa advertida.');
        if (in_array($type, ['goal', 'own_goal', 'assist'], true) && !$athleteId) return $this->fail('Selecione o atleta do registro.');
        if ($athleteId && !$this->athleteInLineup($match, $teamId, $athleteId, $type === 'own_goal')) return $this->fail('Atleta nao pertence a uma escalacao confirmada valida.');
        if ($staffId && (!$teamId || !$this->staffInLineup($match, $teamId, $staffId))) return $this->fail('Membro da comissao nao pertence a escalacao confirmada.');
        if ($type === 'assist') {
            if (!$relatedAthleteId || !$teamId || !$this->athleteInLineup($match, $teamId, $relatedAthleteId, false)) return $this->fail('Assistencia exige atleta e autor do gol da mesma equipe.');
        }
        if ($type === 'own_goal' && (!$teamId || !$this->athleteInLineup($match, $this->opponentTeam($match, $teamId), $athleteId, false))) return $this->fail('Gol contra exige atleta da equipe adversaria.');
        if (in_array($type, ['penalty_scored', 'penalty_missed'], true) && $period !== 'penalties') return $this->fail('Registro de disputa de penaltis exige periodo de penaltis.');
        if ($period === 'penalties' && !in_array($type, ['penalty_scored', 'penalty_missed'], true)) return $this->fail('Somente registros de penalti podem usar esse periodo.');
        $minute = MatchOperationRules::minute($data['minute'] ?? null);
        if (($data['minute'] ?? '') !== '' && $minute === null) return $this->fail('Minuto invalido ou fora do limite.');
        $id = $this->operations->createEvent(['match_id' => (int) $match['id'], 'team_id' => $teamId, 'person_type' => $personType, 'athlete_id' => $athleteId, 'team_staff_id' => $staffId, 'related_athlete_id' => $relatedAthleteId, 'event_type' => $type, 'period' => $period, 'minute' => $minute, 'notes' => trim((string) ($data['notes'] ?? '')) ?: null, 'created_by' => (int) $user['id']]);
        $this->audit->record('match_operation.event_created', (int) $user['id'], 'match_operation_event', $id, ['match_id' => $match['id'], 'event_type' => $type], null);
        return ['ok' => true, 'errors' => [], 'id' => $id];
    }

    public function addSubstitution(array $user, array $match, array $data): array
    {
        $operation = $this->operations->ensure((int) $match['id'], (int) $user['id']);
        if ($operation['status'] !== 'open') return $this->fail('Operacao encerrada; substituicao nao pode ser alterada.');
        $teamId = (int) ($data['team_id'] ?? 0);
        $outId = (int) ($data['athlete_out_id'] ?? 0);
        $inId = (int) ($data['athlete_in_id'] ?? 0);
        $period = trim((string) ($data['period'] ?? 'regular'));
        $window = ($data['window_number'] ?? '') === '' ? null : (int) $data['window_number'];
        if (!$this->isMatchTeam($match, $teamId) || !$outId || !$inId || $outId === $inId) return $this->fail('Equipe e atletas da substituicao sao invalidos.');
        if (!MatchOperationRules::period($period) || $period === 'penalties') return $this->fail('Periodo de substituicao invalido.');
        $lineup = $this->lineupForTeam($match, $teamId);
        if (!$lineup || $lineup['status'] !== 'confirmed') return $this->fail('A equipe precisa de escalacao confirmada.');
        $roles = [];
        foreach ($lineup['players'] as $player) $roles[(int) $player['athlete_id']] = $player['role'];
        if (($roles[$outId] ?? null) !== 'starter' || ($roles[$inId] ?? null) !== 'reserve') return $this->fail('Substituicao exige titular que sai e reserva que entra.');
        $settings = $this->operations->matchSettings((int) $match['championship_id']);
        $existing = $this->operations->substitutions((int) $match['id']);
        $valid = array_values(array_filter($existing, static fn (array $item): bool => (int) $item['valid'] === 1));
        if (count($valid) >= (int) ($settings['substitutions_allowed'] ?? 0)) return $this->fail('Limite de substituicoes do regulamento atingido.');
        if ($window === null || $window < 1 || $window > (int) ($settings['substitution_windows'] ?? 0)) return $this->fail('Janela de substituicao invalida.');
        $minute = MatchOperationRules::minute($data['minute'] ?? null);
        if (($data['minute'] ?? '') !== '' && $minute === null) return $this->fail('Minuto invalido ou fora do limite.');
        $id = $this->operations->createSubstitution(['match_id' => (int) $match['id'], 'team_id' => $teamId, 'athlete_out_id' => $outId, 'athlete_in_id' => $inId, 'period' => $period, 'window_number' => $window, 'minute' => $minute, 'notes' => trim((string) ($data['notes'] ?? '')) ?: null, 'created_by' => (int) $user['id']]);
        $this->audit->record('match_operation.substitution_created', (int) $user['id'], 'match_substitution', $id, ['match_id' => $match['id'], 'team_id' => $teamId], null);
        return ['ok' => true, 'errors' => [], 'id' => $id];
    }

    public function saveOfficials(array $user, array $match, array $data): array
    {
        $operation = $this->operations->ensure((int) $match['id'], (int) $user['id']);
        if ($operation['status'] !== 'open') return $this->fail('Arbitragem bloqueada apos finalizacao.');
        $officials = [];
        foreach (MatchOperationRules::OFFICIAL_ROLES as $role) $officials[$role] = trim((string) ($data[$role] ?? ''));
        $this->operations->saveOfficials((int) $match['id'], $officials, (int) $user['id']);
        $this->audit->record('match_operation.officials_saved', (int) $user['id'], 'match', (int) $match['id'], [], null);
        return ['ok' => true, 'errors' => []];
    }

    public function saveTimes(array $user, array $match, array $data): array
    {
        $operation = $this->operations->ensure((int) $match['id'], (int) $user['id']);
        if ($operation['status'] !== 'open') return $this->fail('Horarios bloqueados apos finalizacao.');
        $times = [];
        foreach (['first_half_started_at', 'first_half_ended_at', 'second_half_started_at', 'second_half_ended_at', 'extra_time_started_at', 'extra_time_ended_at'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value === '') { $times[$field] = null; continue; }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value) ?: \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
            if (!$date) return $this->fail('Horario invalido: ' . $field . '.');
            $times[$field] = $date->format('Y-m-d H:i:s');
        }
        $this->operations->updateTimes((int) $operation['id'], $times);
        return ['ok' => true, 'errors' => []];
    }

    public function saveAdministrativeResult(array $user, array $match, array $data): array
    {
        $operation = $this->operations->ensure((int) $match['id'], (int) $user['id']);
        if ($operation['status'] !== 'open') return $this->fail('Resultado administrativo bloqueado apos finalizacao.');
        $home = filter_var($data['home_score'] ?? null, FILTER_VALIDATE_INT);
        $away = filter_var($data['away_score'] ?? null, FILTER_VALIDATE_INT);
        if ($home === false || $away === false || $home < 0 || $away < 0 || trim((string) ($data['reason'] ?? '')) === '') return $this->fail('Resultado administrativo exige placares validos e motivo.');
        $this->operations->setAdministrativeResult((int) $operation['id'], (int) $home, (int) $away, trim((string) $data['reason']), (int) $user['id']);
        $this->audit->record('match_operation.administrative_result_saved', (int) $user['id'], 'match', (int) $match['id'], ['home_score' => $home, 'away_score' => $away], null);
        return ['ok' => true, 'errors' => []];
    }

    public function finish(array $user, array $match, bool $confirmed): array
    {
        if (!$confirmed) return $this->fail('Confirme o checklist antes de finalizar.');
        $operation = $this->operations->ensure((int) $match['id'], (int) $user['id']);
        if ($operation['status'] !== 'open') return $this->fail('Operacao ja finalizada.');
        $checklist = $this->checklist($match, $operation);
        if ($checklist['errors'] !== []) return ['ok' => false, 'errors' => $checklist['errors']];
        $this->operations->finish((int) $operation['id'], (int) $match['id'], (int) $user['id']);
        $this->audit->record('match_operation.finished', (int) $user['id'], 'match', (int) $match['id'], ['score' => $this->operations->score($operation)], null);
        return ['ok' => true, 'errors' => []];
    }

    public function homologate(array $user, array $match, bool $confirmed): array
    {
        if (!$confirmed) return $this->fail('Confirme a homologacao.');
        $operation = $this->operations->ensure((int) $match['id'], (int) $user['id']);
        if ($operation['status'] !== 'awaiting_homologation') return $this->fail('A partida ainda nao esta aguardando homologacao.');
        $checklist = $this->checklist($match, $operation);
        if ($checklist['errors'] !== []) return ['ok' => false, 'errors' => $checklist['errors']];
        $this->operations->homologate((int) $operation['id'], (int) $match['id'], (int) $user['id']);
        if ($this->discipline) {
            $processed = $this->discipline->processHomologatedMatch(array_merge($match, ['id' => (int) $match['id'], 'status' => 'homologated']), (int) $user['id']);
            if (!$processed['ok']) return ['ok' => false, 'errors' => $processed['errors']];
        }
        if ($this->reports) {
            $report = $this->reports->generateForHomologatedMatch(array_merge($match, ['id' => (int) $match['id'], 'status' => 'homologated']), (int) $user['id']);
            if (!$report['ok']) return ['ok' => false, 'errors' => $report['errors']];
        }
        if ($this->competition) {
            $progress = $this->competition->afterHomologation(array_merge($match, ['status' => 'homologated']), (int) $user['id']);
            if (!$progress['ok']) return ['ok' => false, 'errors' => $progress['errors'] ?? ['Nao foi possivel atualizar os dados derivados da competicao.']];
        }
        $this->audit->record('match_operation.homologated', (int) $user['id'], 'match', (int) $match['id'], ['score' => $this->operations->score($operation)], null);
        return ['ok' => true, 'errors' => []];
    }

    public function checklist(array $match, array $operation): array
    {
        $errors = [];
        $lineupOk = true;
        foreach ([(int) $match['home_team_id'], (int) $match['away_team_id']] as $teamId) {
            $lineup = $this->lineupForTeam($match, $teamId);
            $starters = $lineup ? count(array_filter($lineup['players'], static fn (array $player): bool => $player['role'] === 'starter')) : 0;
            if (!$lineup || $lineup['status'] !== 'confirmed' || $starters !== 11) { $lineupOk = false; break; }
        }
        if (!$lineupOk) $errors[] = 'As duas escalacoes confirmadas sao obrigatorias.';
        $officials = $this->operations->officials((int) $match['id']);
        $hasReferee = (bool) array_filter($officials, static fn (array $official): bool => $official['role'] === 'referee' && trim($official['display_name']) !== '');
        if (!$hasReferee) $errors[] = 'Arbitragem exige um arbitro principal.';
        $timesOk = true;
        foreach (['first_half_started_at', 'first_half_ended_at', 'second_half_started_at', 'second_half_ended_at'] as $field) {
            if (empty($operation[$field])) { $timesOk = false; break; }
        }
        if (!$timesOk) $errors[] = 'Horarios de inicio e fim dos tempos sao obrigatorios.';
        return ['ready' => $errors === [], 'errors' => array_values(array_unique($errors)), 'lineups' => $lineupOk, 'score' => true, 'goals' => true, 'cards' => true, 'substitutions' => true, 'officials' => $hasReferee, 'times' => $timesOk, 'occurrences' => true, 'penalties' => true];
    }

    private function playersForMatch(array $match): array
    {
        $players = [];
        foreach ([(int) $match['home_team_id'], (int) $match['away_team_id']] as $teamId) {
            $lineup = $this->lineupForTeam($match, $teamId);
            foreach ($lineup['players'] ?? [] as $player) $players[] = ['team_id' => $teamId, 'athlete_id' => (int) $player['athlete_id'], 'role' => $player['role'], 'name' => $player['sporting_name'] ?: $player['full_name'], 'number' => $player['shirt_number'] ?: $player['preferred_number']];
        }
        return $players;
    }

    private function staffForMatch(array $match): array
    {
        $staff = [];
        foreach ([(int) $match['home_team_id'], (int) $match['away_team_id']] as $teamId) {
            $lineup = $this->lineupForTeam($match, $teamId);
            foreach ($lineup['staff'] ?? [] as $member) $staff[] = ['team_id' => $teamId, 'team_staff_id' => (int) $member['team_staff_id'], 'name' => $member['display_name'] ?: $member['full_name']];
        }
        return $staff;
    }

    private function lineupForTeam(array $match, int $teamId): ?array
    {
        return $this->lineups->find((int) $match['id'], $teamId);
    }

    private function athleteInLineup(array $match, ?int $teamId, int $athleteId, bool $opponent): bool
    {
        $teams = $teamId ? [$opponent ? $this->opponentTeam($match, $teamId) : $teamId] : [(int) $match['home_team_id'], (int) $match['away_team_id']];
        foreach ($teams as $candidate) {
            $lineup = $this->lineupForTeam($match, $candidate);
            if (!$lineup || $lineup['status'] !== 'confirmed') continue;
            foreach ($lineup['players'] as $player) if ((int) $player['athlete_id'] === $athleteId) return true;
        }
        return false;
    }

    private function isMatchTeam(array $match, int $teamId): bool
    {
        return in_array($teamId, [(int) $match['home_team_id'], (int) $match['away_team_id']], true);
    }

    private function staffInLineup(array $match, int $teamId, int $staffId): bool
    {
        $lineup = $this->lineupForTeam($match, $teamId);
        if (!$lineup || $lineup['status'] !== 'confirmed') return false;
        foreach ($lineup['staff'] as $staff) if ((int) $staff['team_staff_id'] === $staffId && (int) ($staff['present'] ?? 1) === 1) return true;
        return false;
    }

    private function opponentTeam(array $match, int $teamId): int
    {
        return (int) $match['home_team_id'] === $teamId ? (int) $match['away_team_id'] : (int) $match['home_team_id'];
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'errors' => [$message]];
    }
}
