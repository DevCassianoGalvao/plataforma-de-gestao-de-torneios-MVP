<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Repositories\ChampionshipRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TeamRepository;

final class ScheduleService
{
    public function __construct(private readonly ScheduleRepository $schedules, private readonly ChampionshipRepository $championships, private readonly TeamRepository $teams, private readonly AuditService $audit)
    {
    }

    public function createPhase(int $userId, array $data, ?Request $request = null): array
    {
        $errors = ScheduleRules::validatePhase($data);
        if ($errors !== []) return ['ok' => false, 'errors' => $errors];
        try { $id = $this->schedules->createPhase($data, $userId); } catch (\PDOException) { return ['ok' => false, 'errors' => ['Slug da fase ja existe neste campeonato.']]; }
        $this->audit->record('schedule.phase_created', $userId, 'competition_phase', $id, ['championship_id' => $data['championship_id']], $request);
        return ['ok' => true, 'id' => $id, 'errors' => []];
    }

    public function updatePhase(int $userId, array $phase, array $data, ?Request $request = null): array
    {
        if ($phase['status'] !== 'draft') return ['ok' => false, 'errors' => ['Fase publicada ou iniciada nao pode ser editada.']];
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['slug'] = trim((string) ($data['slug'] ?? ''));
        $errors = ScheduleRules::validatePhase($data);
        if ($errors !== []) return ['ok' => false, 'errors' => $errors];
        try { $this->schedules->updatePhase((int) $phase['id'], $data); } catch (\PDOException) { return ['ok' => false, 'errors' => ['Slug da fase ja existe neste campeonato.']]; }
        $this->audit->record('schedule.phase_updated', $userId, 'competition_phase', (int) $phase['id'], [], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function createGroup(int $userId, array $phase, array $data, ?Request $request = null): array
    {
        if (!in_array($phase['status'], ['draft', 'published'], true)) return ['ok' => false, 'errors' => ['Grupo bloqueado apos o inicio da fase.']];
        if (trim((string) ($data['name'] ?? '')) === '' || trim((string) ($data['code'] ?? '')) === '') return ['ok' => false, 'errors' => ['Nome e codigo do grupo sao obrigatorios.']];
        $data['phase_id'] = (int) $phase['id'];
        $data['teams_limit'] = (int) ($data['teams_limit'] ?: $phase['teams_per_group']);
        $data['qualified_limit'] = (int) ($data['qualified_limit'] ?: $phase['qualified_per_group']);
        try { $id = $this->schedules->createGroup($data); } catch (\PDOException) { return ['ok' => false, 'errors' => ['Codigo do grupo ja existe nesta fase.']]; }
        $this->audit->record('schedule.group_created', $userId, 'competition_group', $id, ['phase_id' => $phase['id']], $request);
        return ['ok' => true, 'id' => $id, 'errors' => []];
    }

    public function updateGroup(int $userId, array $group, array $data, ?Request $request = null): array
    {
        if (!in_array($group['phase_status'], ['draft', 'published'], true) || !in_array($group['status'], ['draft', 'published'], true)) return ['ok' => false, 'errors' => ['Grupo bloqueado apos o inicio.']];
        $data['name'] = trim((string) ($data['name'] ?? ''));
        $data['code'] = trim((string) ($data['code'] ?? ''));
        $data['display_order'] = (int) ($data['display_order'] ?? 1);
        $data['teams_limit'] = (int) ($data['teams_limit'] ?? $group['teams_limit']);
        $data['qualified_limit'] = (int) ($data['qualified_limit'] ?? $group['qualified_limit']);
        $currentTeams = count(array_filter($this->schedules->listGroupTeams((int) $group['id']), static fn (array $item): bool => $item['status'] === 'active'));
        if ($data['name'] === '' || $data['code'] === '' || $data['teams_limit'] < $currentTeams || $data['qualified_limit'] < 1 || $data['qualified_limit'] > $data['teams_limit']) return ['ok' => false, 'errors' => ['Configuracao do grupo invalida.']];
        try { $this->schedules->updateGroup((int) $group['id'], $data); } catch (\PDOException) { return ['ok' => false, 'errors' => ['Codigo do grupo ja existe nesta fase.']]; }
        $this->audit->record('schedule.group_updated', $userId, 'competition_group', (int) $group['id'], [], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function addTeam(int $userId, array $group, int $teamId, ?int $position, ?Request $request = null): array
    {
        if (!in_array($group['phase_status'], ['draft', 'published'], true) || !in_array($group['status'], ['draft', 'published'], true)) return ['ok' => false, 'errors' => ['Grupo bloqueado apos o inicio.']];
        $team = $this->teams->findForUser($teamId, 0, 'administrator');
        if (!$team || (int) $team['championship_id'] !== (int) $group['championship_id']) return ['ok' => false, 'errors' => ['Equipe fora do campeonato.']];
        $existing = $this->schedules->listGroupTeams((int) $group['id']);
        foreach ($existing as $item) if ((int) $item['team_id'] !== $teamId && $item['status'] === 'active' && $position !== null && (int) $item['position'] === $position) return ['ok' => false, 'errors' => ['Posicao do grupo ja esta ocupada.']];
        $available = $this->schedules->listAvailableTeams((int) $group['championship_id'], (int) $group['phase_id'], (int) $group['id']);
        $allowed = false;
        foreach ($available as $item) if ((int) $item['id'] === $teamId) $allowed = true;
        if (!$allowed && !$this->schedules->groupTeam((int) $group['id'], $teamId)) return ['ok' => false, 'errors' => ['Equipe ja esta em outro grupo desta fase.']];
        if (count(array_filter($existing, static fn (array $item): bool => $item['status'] === 'active' && (int) $item['team_id'] !== $teamId)) >= (int) $group['teams_limit'] && !$this->schedules->groupTeam((int) $group['id'], $teamId)) return ['ok' => false, 'errors' => ['Grupo atingiu quantidade maxima de equipes.']];
        $this->schedules->addTeam((int) $group['phase_id'], (int) $group['id'], $teamId, $position);
        $this->audit->record('schedule.team_added_to_group', $userId, 'competition_group', (int) $group['id'], ['team_id' => $teamId], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function withdrawTeam(int $userId, array $group, int $teamId, ?Request $request = null): array
    {
        if (!in_array($group['status'], ['draft', 'published'], true)) return ['ok' => false, 'errors' => ['Equipe nao pode ser retirada apos inicio do grupo.']];
        $this->schedules->withdrawTeam((int) $group['id'], $teamId);
        $this->audit->record('schedule.team_withdrawn_from_group', $userId, 'competition_group', (int) $group['id'], ['team_id' => $teamId], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function moveTeam(int $userId, array $source, array $target, int $teamId, int $position, ?Request $request = null): array
    {
        if ((int) $source['phase_id'] !== (int) $target['phase_id']) return ['ok' => false, 'errors' => ['Grupos pertencem a fases diferentes.']];
        if (!in_array($source['phase_status'], ['draft', 'published'], true) || !in_array($source['status'], ['draft', 'published'], true) || !in_array($target['status'], ['draft', 'published'], true)) return ['ok' => false, 'errors' => ['Distribuicao bloqueada apos inicio da fase.']];
        $membership = $this->schedules->groupTeam((int) $source['id'], $teamId);
        if (!$membership || $membership['status'] !== 'active') return ['ok' => false, 'errors' => ['Equipe nao esta ativa no grupo de origem.']];
        $targetTeams = array_filter($this->schedules->listGroupTeams((int) $target['id']), static fn (array $item): bool => $item['status'] === 'active');
        if (count($targetTeams) >= (int) $target['teams_limit']) return ['ok' => false, 'errors' => ['Grupo de destino atingiu quantidade maxima de equipes.']];
        foreach ($targetTeams as $item) if ((int) $item['position'] === $position) return ['ok' => false, 'errors' => ['Posicao do grupo de destino ja esta ocupada.']];
        $this->schedules->updateGroupTeam((int) $source['id'], $teamId, (int) $target['id'], $position);
        $this->audit->record('schedule.team_moved_between_groups', $userId, 'competition_group', (int) $target['id'], ['source_group_id' => $source['id'], 'team_id' => $teamId], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function publishPhase(int $userId, array $phase, ?Request $request = null): array
    {
        $groups = $this->schedules->listGroups((int) $phase['id']);
        $errors = [];
        if (count($groups) !== (int) $phase['group_count']) $errors[] = 'Quantidade de grupos diferente da configuracao da fase.';
        foreach ($groups as $group) if ((int) $group['active_teams_count'] !== (int) $group['teams_limit']) $errors[] = $group['name'] . ' precisa ter ' . (int) $group['teams_limit'] . ' equipes.';
        if ($errors !== []) return ['ok' => false, 'errors' => $errors];
        $this->schedules->updatePhaseStatus((int) $phase['id'], 'published');
        foreach ($groups as $group) $this->schedules->updateGroupStatus((int) $group['id'], 'published');
        $this->audit->record('schedule.phase_published', $userId, 'competition_phase', (int) $phase['id'], [], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function startPhase(int $userId, array $phase, ?Request $request = null): array
    {
        if ($phase['status'] !== 'published') return ['ok' => false, 'errors' => ['Publique a fase antes de inicia-la.']];
        $this->schedules->updatePhaseStatus((int) $phase['id'], 'in_progress');
        foreach ($this->schedules->listGroups((int) $phase['id']) as $group) $this->schedules->updateGroupStatus((int) $group['id'], 'in_progress');
        $this->audit->record('schedule.phase_started', $userId, 'competition_phase', (int) $phase['id'], [], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function preview(int $userId, array $phase, array $data): array
    {
        $validation = ScheduleRules::validateSchedule($data);
        if ($validation !== []) return ['ok' => false, 'errors' => $validation, 'matches' => [], 'conflicts' => []];
        $matches = $this->buildMatches($phase, $data);
        $conflicts = $this->internalConflicts($matches);
        foreach ($matches as $match) if ($this->schedules->matchByFixture($match['fixture_key']) === null && $this->schedules->hasConflict($match)) $conflicts[] = $match['fixture_key'];
        return ['ok' => true, 'errors' => [], 'matches' => $matches, 'conflicts' => array_values(array_unique($conflicts))];
    }

    public function generate(int $userId, array $phase, array $data, ?Request $request = null): array
    {
        if ($phase['status'] !== 'published') return ['ok' => false, 'errors' => ['Publique a fase antes de gerar a tabela.']];
        $preview = $this->preview($userId, $phase, $data);
        if (!$preview['ok']) return $preview;
        if ($preview['conflicts'] !== []) return ['ok' => false, 'errors' => ['Existem conflitos de equipe ou local na agenda.'], 'matches' => $preview['matches'], 'conflicts' => $preview['conflicts']];
        $this->schedules->begin();
        try {
            foreach ($preview['matches'] as $match) {
                $roundId = $this->schedules->createRound(['phase_id' => $match['phase_id'], 'group_id' => $match['group_id'], 'round_number' => $match['round_number'], 'period_start' => $match['match_date'], 'period_end' => $match['match_date'], 'status' => 'published']);
                $match['round_id'] = $roundId;
                $this->schedules->createMatch($match, $userId);
            }
            $this->schedules->commit();
        } catch (\Throwable $exception) { $this->schedules->rollBack(); throw $exception; }
        $this->audit->record('schedule.generated', $userId, 'competition_phase', (int) $phase['id'], ['matches' => count($preview['matches'])], $request);
        foreach ($preview['matches'] as $index => $match) {
            $round = $this->schedules->round((int) $match['phase_id'], (int) $match['group_id'], (int) $match['round_number']);
            $preview['matches'][$index]['round_id'] = (int) ($round['id'] ?? 0);
        }
        return ['ok' => true, 'errors' => [], 'matches' => $preview['matches'], 'conflicts' => []];
    }

    public function changeAgenda(int $userId, array $match, array $data, string $action, ?Request $request = null): array
    {
        if (in_array($match['status'], ['cancelled', 'finished', 'homologated'], true)) return ['ok' => false, 'errors' => ['Partida encerrada e sem agenda editavel.']];
        if (trim((string) ($data['reason'] ?? '')) === '') return ['ok' => false, 'errors' => ['Informe o motivo da alteracao.']];
        $data['status'] = $data['status'] ?: ($action === 'postpone' ? 'postponed' : $match['status']);
        if ($action === 'cancel') {
            $data['match_date'] = $data['match_date'] ?: $match['match_date'];
            $data['match_time'] = $data['match_time'] ?: $match['match_time'];
            $data['venue_id'] = $data['venue_id'] ?: $match['venue_id'];
        }
        $candidate = array_merge($match, ['match_date' => $data['match_date'], 'match_time' => $data['match_time'], 'venue_id' => $data['venue_id'], 'status' => $data['status']]);
        if ($action !== 'cancel' && $this->schedules->hasConflict($candidate, (int) $match['id'])) return ['ok' => false, 'errors' => ['Novo horario ou local conflita com outra partida.']];
        $this->schedules->updateMatchAgenda((int) $match['id'], $data);
        $this->schedules->recordScheduleChange((int) $match['id'], ['action' => $action, 'from_date' => $match['match_date'], 'from_time' => $match['match_time'], 'from_venue_id' => $match['venue_id'], 'to_date' => $data['match_date'], 'to_time' => $data['match_time'], 'to_venue_id' => $data['venue_id'], 'reason' => $data['reason']], $userId);
        $this->audit->record('schedule.' . $action, $userId, 'match', (int) $match['id'], ['reason' => $data['reason']], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function changeStatus(int $userId, array $match, string $status, ?Request $request = null): array
    {
        if (!ScheduleRules::canTransitionMatch((string) $match['status'], $status)) return ['ok' => false, 'errors' => ['Transicao de partida invalida.']];
        $this->schedules->updateMatchAgenda((int) $match['id'], ['match_date' => $match['match_date'], 'match_time' => $match['match_time'], 'venue_id' => $match['venue_id'], 'status' => $status, 'observation' => $match['observation']]);
        $this->audit->record('schedule.status_changed', $userId, 'match', (int) $match['id'], ['from' => $match['status'], 'to' => $status], $request);
        return ['ok' => true, 'errors' => []];
    }

    public function addDecision(int $userId, array $match, array $data, ?Request $request = null): array
    {
        if (trim((string) ($data['notes'] ?? '')) === '') return ['ok' => false, 'errors' => ['Informe a decisao administrativa.']];
        $this->schedules->createDecision(['championship_id' => $match['championship_id'], 'phase_id' => $match['phase_id'], 'group_id' => $match['group_id'], 'match_id' => $match['id'], 'team_id' => $data['team_id'] ?? null, 'decision_type' => $data['decision_type'] ?: 'schedule', 'status' => 'recorded', 'notes' => $data['notes']], $userId);
        $this->audit->record('schedule.decision_recorded', $userId, 'match', (int) $match['id'], ['decision_type' => $data['decision_type'] ?? 'schedule'], $request);
        return ['ok' => true, 'errors' => []];
    }

    private function buildMatches(array $phase, array $data): array
    {
        $groups = $this->schedules->listGroups((int) $phase['id']);
        $selected = array_map('intval', (array) ($data['group_ids'] ?? []));
        if ($selected !== []) $groups = array_values(array_filter($groups, static fn (array $group): bool => in_array((int) $group['id'], $selected, true)));
        $venues = array_values(array_map('intval', (array) ($data['venue_ids'] ?? [])));
        $days = array_values(array_map('intval', (array) ($data['allowed_days'] ?? [])));
        $date = new \DateTimeImmutable((string) $data['period_start']);
        $end = new \DateTimeImmutable((string) $data['period_end']);
        $matches = [];
        foreach ($groups as $group) {
            $teamIds = array_map('intval', array_column(array_filter($this->schedules->listGroupTeams((int) $group['id']), static fn (array $item): bool => $item['status'] === 'active'), 'team_id'));
            $rounds = RoundRobinGenerator::generate($teamIds, !empty($data['return_leg']));
            foreach ($rounds as $roundIndex => $roundMatches) {
                $date = $this->nextAllowedDate($date, $end, $days);
                if (!$date) break;
                foreach ($roundMatches as $order => $pair) {
                    $time = $this->slotTime((string) $data['start_time'], $order, (int) ($data['slot_minutes'] ?: 90));
                    $venueId = $venues[$order % count($venues)] ?? null;
                    $key = hash('sha256', implode(':', [(int) $phase['id'], (int) $group['id'], (int) $pair['round_number'], (int) $pair['leg_number'], (int) $pair['home_team_id'], (int) $pair['away_team_id']]));
                    $matches[] = ['championship_id' => $phase['championship_id'], 'phase_id' => $phase['id'], 'group_id' => $group['id'], 'round_id' => 0, 'round_number' => $pair['round_number'], 'home_team_id' => $pair['home_team_id'], 'away_team_id' => $pair['away_team_id'], 'venue_id' => $venueId, 'fixture_key' => $key, 'leg_number' => $pair['leg_number'], 'match_order' => $order + 1, 'match_date' => $date->format('Y-m-d'), 'match_time' => $time, 'status' => 'scheduled', 'observation' => null];
                }
                $date = $date->modify('+1 day');
            }
        }
        return $matches;
    }

    private function nextAllowedDate(\DateTimeImmutable $date, \DateTimeImmutable $end, array $days): ?\DateTimeImmutable
    {
        for ($i = 0; $i < 370 && $date <= $end; $i++, $date = $date->modify('+1 day')) if (in_array((int) $date->format('N'), $days, true)) return $date;
        return null;
    }

    private function slotTime(string $start, int $order, int $minutes): string
    {
        $date = \DateTimeImmutable::createFromFormat('H:i', $start) ?: new \DateTimeImmutable('08:00');
        return $date->modify('+' . ($order * max(30, $minutes)) . ' minutes')->format('H:i:s');
    }

    private function internalConflicts(array $matches): array
    {
        $seen = [];
        $conflicts = [];
        foreach ($matches as $match) {
            $time = $match['match_date'] . '|' . $match['match_time'];
            foreach ([(int) $match['home_team_id'], (int) $match['away_team_id']] as $teamId) {
                $key = 'team:' . $match['championship_id'] . ':' . $time . ':' . $teamId;
                if (isset($seen[$key])) $conflicts[] = $match['fixture_key'];
                $seen[$key] = true;
            }
            if (!empty($match['venue_id'])) {
                $key = 'venue:' . $match['championship_id'] . ':' . $time . ':' . (int) $match['venue_id'];
                if (isset($seen[$key])) $conflicts[] = $match['fixture_key'];
                $seen[$key] = true;
            }
        }
        return array_values(array_unique($conflicts));
    }
}
