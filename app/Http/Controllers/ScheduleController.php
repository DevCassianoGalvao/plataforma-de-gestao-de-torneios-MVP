<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ChampionshipRepository;
use App\Repositories\ScheduleRepository;
use App\Services\ScheduleAccessService;
use App\Services\ScheduleRules;
use App\Services\ScheduleService;

final class ScheduleController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly ScheduleAccessService $access, private readonly ScheduleService $service, private readonly ScheduleRepository $schedules, private readonly ChampionshipRepository $championships)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'schedule.view');
        if ($guard instanceof Response) return $guard;
        $filters = ['championship_id' => (string) ($request->query['championship_id'] ?? ''), 'phase_id' => (string) ($request->query['phase_id'] ?? ''), 'group_id' => (string) ($request->query['group_id'] ?? ''), 'round_number' => (string) ($request->query['round_number'] ?? ''), 'team_id' => (string) ($request->query['team_id'] ?? ''), 'from' => (string) ($request->query['from'] ?? ''), 'to' => (string) ($request->query['to'] ?? ''), 'status' => (string) ($request->query['status'] ?? ''), 'upcoming' => (string) ($request->query['upcoming'] ?? '')];
        $items = $this->access->listMatches($guard, $filters);
        $teamOptions = $phaseOptions = $groupOptions = [];
        foreach ($items as $item) {
            foreach ([['id' => $item['home_team_id'], 'name' => $item['home_team_name']], ['id' => $item['away_team_id'], 'name' => $item['away_team_name']]] as $teamOption) $teamOptions[(int) $teamOption['id']] = $teamOption['name'];
            $phaseOptions[(int) $item['phase_id']] = $item['phase_name'];
            $groupOptions[(int) $item['group_id']] = $item['group_name'];
        }
        return $this->page('Tabela e partidas', 'admin/schedule/index', ['user' => $guard, 'items' => $items, 'championships' => $this->championshipOptions($guard), 'teamOptions' => $teamOptions, 'phaseOptions' => $phaseOptions, 'groupOptions' => $groupOptions, 'filters' => $filters, 'message' => Session::consumeFlash('schedule_message')]);
    }

    public function phases(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'phases.view');
        if ($guard instanceof Response) return $guard;
        $championships = $this->championshipOptions($guard);
        $championshipId = (int) ($request->query['championship_id'] ?? ($championships[0]['id'] ?? 0));
        if (!$this->access->championship($guard, $championshipId)) return Response::forbidden();
        return $this->page('Fases', 'admin/schedule/phases', ['user' => $guard, 'championships' => $championships, 'championship_id' => $championshipId, 'phases' => $this->schedules->listPhases($championshipId), 'canCreate' => $this->authorization->can($guard, 'phases.create'), 'message' => Session::consumeFlash('schedule_message')]);
    }

    public function createPhase(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'phases.create');
        if ($guard instanceof Response) return $guard;
        $data = ['championship_id' => (int) ($request->body['championship_id'] ?? 0), 'name' => trim((string) ($request->body['name'] ?? '')), 'slug' => trim((string) ($request->body['slug'] ?? '')), 'phase_type' => trim((string) ($request->body['phase_type'] ?? 'groups')), 'sequence_number' => (int) ($request->body['sequence_number'] ?? 1), 'group_count' => (int) ($request->body['group_count'] ?? 2), 'teams_per_group' => (int) ($request->body['teams_per_group'] ?? 5), 'qualified_per_group' => (int) ($request->body['qualified_per_group'] ?? 4)];
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        if (!$this->access->championship($guard, $data['championship_id'])) return Response::forbidden();
        $result = $this->service->createPhase((int) $guard['id'], $data, $request);
        if (!$result['ok']) return $this->errorPage('Fases', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Fase criada.');
        return Response::redirect(Config::url('/admin/fases?championship_id=' . $data['championship_id']));
    }

    public function publishPhase(Request $request, array $params = []): Response
    {
        [$guard, $phase] = $this->phaseContext($request, (int) ($params[0] ?? 0), 'phases.publish');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->publishPhase((int) $guard['id'], $phase, $request);
        if (!$result['ok']) return $this->errorPage('Fases', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Fase e grupos publicados.');
        return Response::redirect(Config::url('/admin/fases?championship_id=' . $phase['championship_id']));
    }

    public function startPhase(Request $request, array $params = []): Response
    {
        [$guard, $phase] = $this->phaseContext($request, (int) ($params[0] ?? 0), 'phases.publish');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->startPhase((int) $guard['id'], $phase, $request);
        if (!$result['ok']) return $this->errorPage('Fases', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Fase iniciada e grupos bloqueados.');
        return Response::redirect(Config::url('/admin/fases?championship_id=' . $phase['championship_id']));
    }

    public function groups(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'groups.view');
        if ($guard instanceof Response) return $guard;
        $phaseId = (int) ($request->query['phase_id'] ?? 0);
        $phase = $this->schedules->phase($phaseId);
        if (!$phase || !$this->access->championship($guard, (int) $phase['championship_id'])) return Response::forbidden();
        $groups = $this->schedules->listGroups($phaseId);
        $groupTeams = [];
        foreach ($groups as $group) $groupTeams[(int) $group['id']] = $this->schedules->listGroupTeams((int) $group['id']);
        return $this->page('Grupos', 'admin/schedule/groups', ['user' => $guard, 'phase' => $phase, 'groups' => $groups, 'groupTeams' => $groupTeams, 'teams' => $this->schedules->listAvailableTeams((int) $phase['championship_id'], $phaseId), 'canManage' => $this->access->canManage($guard, (int) $phase['championship_id']), 'message' => Session::consumeFlash('schedule_message')]);
    }

    public function createGroup(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'groups.create');
        if ($guard instanceof Response) return $guard;
        $phase = $this->schedules->phase((int) ($request->body['phase_id'] ?? 0));
        if (!$phase || !$this->access->canManage($guard, (int) $phase['championship_id'])) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->createGroup((int) $guard['id'], $phase, ['name' => trim((string) ($request->body['name'] ?? '')), 'code' => trim((string) ($request->body['code'] ?? '')), 'display_order' => (int) ($request->body['display_order'] ?? 1), 'teams_limit' => (int) ($request->body['teams_limit'] ?? 0), 'qualified_limit' => (int) ($request->body['qualified_limit'] ?? 0)], $request);
        if (!$result['ok']) return $this->errorPage('Grupos', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Grupo criado.');
        return Response::redirect(Config::url('/admin/grupos?phase_id=' . $phase['id']));
    }

    public function addTeam(Request $request, array $params = []): Response
    {
        [$guard, $group] = $this->groupContext($request, (int) ($params[0] ?? 0), 'groups.distribute');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->addTeam((int) $guard['id'], $group, (int) ($request->body['team_id'] ?? 0), ($request->body['position'] ?? '') === '' ? null : (int) $request->body['position'], $request);
        if (!$result['ok']) return $this->errorPage('Grupos', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Equipe distribuida no grupo.');
        return Response::redirect(Config::url('/admin/grupos?phase_id=' . $group['phase_id']));
    }

    public function updateGroup(Request $request, array $params = []): Response
    {
        [$guard, $group] = $this->groupContext($request, (int) ($params[0] ?? 0), 'groups.update');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->updateGroup((int) $guard['id'], $group, $request->body, $request);
        if (!$result['ok']) return $this->errorPage('Grupos', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Grupo atualizado.');
        return Response::redirect(Config::url('/admin/grupos?phase_id=' . $group['phase_id']));
    }

    public function moveTeam(Request $request, array $params = []): Response
    {
        [$guard, $source] = $this->groupContext($request, (int) ($params[0] ?? 0), 'groups.distribute');
        if ($guard instanceof Response) return $guard;
        $target = $this->schedules->group((int) ($request->body['target_group_id'] ?? 0));
        if (!$target || (int) $target['phase_id'] !== (int) $source['phase_id']) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->moveTeam((int) $guard['id'], $source, $target, (int) ($params[1] ?? 0), (int) ($request->body['position'] ?? 1), $request);
        if (!$result['ok']) return $this->errorPage('Grupos', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Equipe movida de grupo.');
        return Response::redirect(Config::url('/admin/grupos?phase_id=' . $source['phase_id']));
    }

    public function withdrawTeam(Request $request, array $params = []): Response
    {
        [$guard, $group] = $this->groupContext($request, (int) ($params[0] ?? 0), 'groups.distribute');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->withdrawTeam((int) $guard['id'], $group, (int) ($params[1] ?? 0), $request);
        if (!$result['ok']) return $this->errorPage('Grupos', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Equipe retirada do grupo.');
        return Response::redirect(Config::url('/admin/grupos?phase_id=' . $group['phase_id']));
    }

    public function venues(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'venues.view');
        if ($guard instanceof Response) return $guard;
        $championships = $this->championshipOptions($guard);
        $championshipId = (int) ($request->query['championship_id'] ?? ($championships[0]['id'] ?? 0));
        if (!$this->access->championship($guard, $championshipId)) return Response::forbidden();
        return $this->page('Locais', 'admin/schedule/venues', ['user' => $guard, 'championships' => $championships, 'championship_id' => $championshipId, 'venues' => $this->schedules->listVenues($championshipId), 'canCreate' => $this->authorization->can($guard, 'venues.create'), 'message' => Session::consumeFlash('schedule_message')]);
    }

    public function createVenue(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'venues.create');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $championshipId = (int) ($request->body['championship_id'] ?? 0);
        if (!$this->access->championship($guard, $championshipId)) return Response::forbidden();
        try { $this->schedules->createVenue(['championship_id' => $championshipId, 'name' => trim((string) ($request->body['name'] ?? '')), 'address' => trim((string) ($request->body['address'] ?? '')), 'city' => trim((string) ($request->body['city'] ?? '')), 'state' => strtoupper(trim((string) ($request->body['state'] ?? ''))), 'capacity' => (int) ($request->body['capacity'] ?? 0), 'status' => 'active'], (int) $guard['id']); } catch (\PDOException) { return $this->errorPage('Locais', 'errors/simple', ['message' => 'Nome do local ja existe neste campeonato.'], 422); }
        Session::flash('schedule_message', 'Local criado.');
        return Response::redirect(Config::url('/admin/locais?championship_id=' . $championshipId));
    }

    public function assistant(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'schedule.generate');
        if ($guard instanceof Response) return $guard;
        $championships = $this->championshipOptions($guard);
        $phaseId = (int) ($request->query['phase_id'] ?? ($request->body['phase_id'] ?? 0));
        $phase = $phaseId ? $this->schedules->phase($phaseId) : null;
        $phases = $phase ? [$phase] : $this->phaseOptions($guard, $championships[0]['id'] ?? 0);
        $data = $this->scheduleInput($request);
        return $this->page('Assistente de tabela', 'admin/schedule/assistant', ['user' => $guard, 'championships' => $championships, 'phases' => $phases, 'phase' => $phase, 'groups' => $phase ? $this->schedules->listGroups($phaseId) : [], 'venues' => $phase ? $this->schedules->listVenues((int) $phase['championship_id']) : [], 'data' => $data, 'preview' => [], 'errors' => [], 'message' => Session::consumeFlash('schedule_message')]);
    }

    public function preview(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'schedule.generate');
        if ($guard instanceof Response) return $guard;
        $data = $this->scheduleInput($request);
        $phase = $this->schedules->phase((int) $data['phase_id']);
        if (!$phase || !$this->access->canGenerate($guard, (int) $phase['championship_id'])) return Response::forbidden();
        $preview = $this->service->preview((int) $guard['id'], $phase, $data);
        return $this->assistantPage($guard, $phase, $data, $preview);
    }

    public function confirm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'schedule.generate');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $data = $this->scheduleInput($request);
        $phase = $this->schedules->phase((int) $data['phase_id']);
        if (!$phase || !$this->access->canGenerate($guard, (int) $phase['championship_id'])) return Response::forbidden();
        $result = $this->service->generate((int) $guard['id'], $phase, $data, $request);
        if (!$result['ok']) return $this->assistantPage($guard, $phase, $data, $result, 422);
        Session::flash('schedule_message', 'Tabela gerada sem duplicar confrontos existentes.');
        return Response::redirect(Config::url('/admin/tabela?championship_id=' . $phase['championship_id']));
    }

    public function match(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'schedule.view');
        if ($guard instanceof Response) return $guard;
        $item = $this->access->findMatch($guard, (int) ($params[0] ?? 0));
        if (!$item) return Response::forbidden();
        return $this->page('Partida e agenda', 'admin/schedule/match', ['user' => $guard, 'item' => $item, 'venues' => $this->schedules->listVenues((int) $item['championship_id']), 'changes' => $this->schedules->scheduleChanges((int) $item['id']), 'decisions' => $this->schedules->decisions((int) $item['id']), 'canManage' => $this->access->canManage($guard, (int) $item['championship_id']), 'message' => Session::consumeFlash('schedule_message')]);
    }

    public function agenda(Request $request, array $params = []): Response
    {
        return $this->agendaAction($request, (int) ($params[0] ?? 0), 'schedule.update', 'reschedule');
    }

    public function postpone(Request $request, array $params = []): Response
    {
        return $this->agendaAction($request, (int) ($params[0] ?? 0), 'schedule.postpone', 'postpone');
    }

    public function cancel(Request $request, array $params = []): Response
    {
        return $this->agendaAction($request, (int) ($params[0] ?? 0), 'schedule.cancel', 'cancel');
    }

    public function confirmMatch(Request $request, array $params = []): Response
    {
        return $this->statusAction($request, (int) ($params[0] ?? 0), 'confirmed');
    }

    public function wo(Request $request, array $params = []): Response
    {
        return $this->statusAction($request, (int) ($params[0] ?? 0), 'wo');
    }

    public function decision(Request $request, array $params = []): Response
    {
        [$guard, $item] = $this->matchContext($request, (int) ($params[0] ?? 0), 'schedule.update');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->addDecision((int) $guard['id'], $item, ['decision_type' => trim((string) ($request->body['decision_type'] ?? 'schedule')), 'team_id' => ($request->body['team_id'] ?? '') === '' ? null : (int) $request->body['team_id'], 'notes' => trim((string) ($request->body['notes'] ?? ''))], $request);
        if (!$result['ok']) return $this->errorPage('Partida', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Decisao administrativa registrada.');
        return Response::redirect(Config::url('/admin/partidas/' . $item['id']));
    }

    private function agendaAction(Request $request, int $id, string $permission, string $action): Response
    {
        [$guard, $item] = $this->matchContext($request, $id, $permission);
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->changeAgenda((int) $guard['id'], $item, ['match_date' => trim((string) ($request->body['match_date'] ?? '')), 'match_time' => trim((string) ($request->body['match_time'] ?? '')), 'venue_id' => ($request->body['venue_id'] ?? '') === '' ? null : (int) $request->body['venue_id'], 'status' => $action === 'cancel' ? 'cancelled' : trim((string) ($request->body['status'] ?? $item['status'])), 'reason' => trim((string) ($request->body['reason'] ?? ''))], $action, $request);
        if (!$result['ok']) return $this->errorPage('Partida', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Agenda atualizada com historico.');
        return Response::redirect(Config::url('/admin/partidas/' . $id));
    }

    private function statusAction(Request $request, int $id, string $status): Response
    {
        [$guard, $item] = $this->matchContext($request, $id, 'schedule.update');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->changeStatus((int) $guard['id'], $item, $status, $request);
        if (!$result['ok']) return $this->errorPage('Partida', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('schedule_message', 'Status da partida atualizado.');
        return Response::redirect(Config::url('/admin/partidas/' . $id));
    }

    private function matchContext(Request $request, int $id, string $permission): array
    {
        $guard = $this->guard($request, $permission);
        if ($guard instanceof Response) return [$guard, null];
        $item = $this->access->findMatch($guard, $id);
        if (!$item || !$this->access->canManage($guard, (int) $item['championship_id'])) return [Response::forbidden(), null];
        return [$guard, $item];
    }

    private function phaseContext(Request $request, int $id, string $permission): array
    {
        $guard = $this->guard($request, $permission);
        if ($guard instanceof Response) return [$guard, null];
        $phase = $this->schedules->phase($id);
        if (!$phase || !$this->access->canManage($guard, (int) $phase['championship_id'])) return [Response::forbidden(), null];
        return [$guard, $phase];
    }

    private function groupContext(Request $request, int $id, string $permission): array
    {
        $guard = $this->guard($request, $permission);
        if ($guard instanceof Response) return [$guard, null];
        $group = $this->schedules->group($id);
        if (!$group || !$this->access->canManage($guard, (int) $group['championship_id'])) return [Response::forbidden(), null];
        return [$guard, $group];
    }

    private function championshipOptions(array $user): array
    {
        $scope = $this->access->scope($user);
        if ($scope === 'administrator') return $this->championships->listForUser(0, true);
        if ($scope === 'organizer') return $this->championships->listForUser((int) $user['id'], false);
        return [];
    }

    private function phaseOptions(array $user, int $championshipId): array
    {
        if (!$championshipId || !$this->access->championship($user, $championshipId)) return [];
        return $this->schedules->listPhases($championshipId);
    }

    private function scheduleInput(Request $request): array
    {
        return ['phase_id' => (int) ($request->body['phase_id'] ?? $request->query['phase_id'] ?? 0), 'group_ids' => array_values(array_filter(array_map('intval', (array) ($request->body['group_ids'] ?? [])))), 'return_leg' => !empty($request->body['return_leg']), 'period_start' => trim((string) ($request->body['period_start'] ?? '')), 'period_end' => trim((string) ($request->body['period_end'] ?? '')), 'allowed_days' => array_values(array_filter(array_map('intval', (array) ($request->body['allowed_days'] ?? [])))), 'start_time' => trim((string) ($request->body['start_time'] ?? '18:00')), 'slot_minutes' => (int) ($request->body['slot_minutes'] ?? 90), 'venue_ids' => array_values(array_filter(array_map('intval', (array) ($request->body['venue_ids'] ?? []))))];
    }

    private function assistantPage(array $user, array $phase, array $data, array $result, int $status = 200): Response
    {
        return $this->errorPage('Assistente de tabela', 'admin/schedule/assistant', ['user' => $user, 'championships' => $this->championshipOptions($user), 'phases' => [$phase], 'phase' => $phase, 'groups' => $this->schedules->listGroups((int) $phase['id']), 'venues' => $this->schedules->listVenues((int) $phase['championship_id']), 'data' => $data, 'preview' => $result['matches'] ?? [], 'conflicts' => $result['conflicts'] ?? [], 'errors' => $result['errors'] ?? []], $status);
    }
}
