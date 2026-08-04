<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\SimulationRepository;
use App\Services\SimulationService;

final class SimulationController extends Controller
{
    public function __construct($users, $authorization, $audit, private readonly SimulationRepository $simulations, private readonly SimulationService $service)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request): Response
    {
        $user = $this->authorized($request, 'simulation.view');
        if ($user instanceof Response) return $user;
        return $this->page('Simulacoes internas', 'admin/simulations/index', ['user' => $user, 'items' => $this->simulations->list(), 'message' => Session::consumeFlash('simulation_message')]);
    }

    public function form(Request $request): Response
    {
        $user = $this->authorized($request, 'simulation.create');
        if ($user instanceof Response) return $user;
        return $this->page('Nova simulacao', 'admin/simulations/form', array_merge(['user' => $user, 'errors' => [], 'record' => []], $this->formData()));
    }

    public function create(Request $request): Response
    {
        $user = $this->authorized($request, 'simulation.create');
        if ($user instanceof Response) return $user;
        if (!$this->validCsrf($request)) return Response::forbidden('Sessao expirada.');
        $result = $this->service->create($request->body, (int) $user['id']);
        if (!$result['ok']) return $this->errorPage('Nova simulacao', 'admin/simulations/form', array_merge(['user' => $user, 'errors' => $result['errors'], 'record' => $request->body], $this->formData()), 422);
        Session::flash('simulation_message', 'Cenario interno criado.');
        return $this->redirect('/admin/simulacoes/' . $result['id']);
    }

    public function show(Request $request, array $params = []): Response
    {
        $user = $this->authorized($request, 'simulation.view');
        if ($user instanceof Response) return $user;
        $scenario = $this->scenario((int) ($params[0] ?? 0));
        if (!$scenario) return Response::forbidden();
        $comparison = $this->scenario((int) ($request->query['comparar'] ?? 0));
        if ($comparison && ((int) $comparison['championship_id'] !== (int) $scenario['championship_id'] || (int) $comparison['phase_id'] !== (int) $scenario['phase_id'])) $comparison = null;
        $groups = $this->simulations->groups((int) $scenario['phase_id']);
        $teams = [];
        foreach ($groups as $group) $teams[(int) $group['id']] = $this->simulations->teams((int) $group['id']);
        return $this->page('Simulacao interna', 'admin/simulations/show', [
            'user' => $user,
            'scenario' => $scenario,
            'comparisonScenario' => $comparison,
            'availableScenarios' => $this->simulations->list(),
            'matches' => $this->simulations->matches((int) $scenario['id']),
            'events' => fn (int $id): array => $this->simulations->events($id),
            'projection' => $comparison ? $this->service->compare((int) $scenario['id'], (int) $comparison['id']) : $this->service->projection((int) $scenario['id']),
            'crossings' => $this->service->possibleCrossings((int) $scenario['id']),
            'referenceMatches' => $this->simulations->officialMatches((int) $scenario['phase_id'], $scenario['group_id'] ? (int) $scenario['group_id'] : null),
            'groups' => $groups,
            'rounds' => $this->simulations->rounds((int) $scenario['phase_id'], $scenario['group_id'] ? (int) $scenario['group_id'] : null),
            'teams' => $teams,
            'canEdit' => $this->authorization->can($user, 'simulation.edit'),
            'canManage' => $this->authorization->can($user, 'simulation.manage'),
            'canCompare' => $this->authorization->can($user, 'simulation.compare'),
            'message' => Session::consumeFlash('simulation_message'),
        ]);
    }

    public function addReference(Request $request, array $params = []): Response { return $this->mutate($request, $params, 'simulation.edit', fn (int $id, array $body, array $user) => $this->service->addReference($id, (int) ($body['reference_match_id'] ?? 0), (int) $user['id'])); }
    public function hypothetical(Request $request, array $params = []): Response { return $this->mutate($request, $params, 'simulation.edit', fn (int $id, array $body, array $user) => $this->service->addHypothetical($id, $body, (int) $user['id'])); }
    public function score(Request $request, array $params = []): Response { return $this->mutate($request, $params, 'simulation.edit', fn (int $id, array $body, array $user) => $this->service->score($id, (int) ($params[1] ?? 0), $body, (int) $user['id'])); }
    public function restore(Request $request, array $params = []): Response { return $this->mutate($request, $params, 'simulation.edit', fn (int $id, array $body, array $user) => $this->service->restoreMatch($id, (int) ($params[1] ?? 0), (int) $user['id'])); }
    public function event(Request $request, array $params = []): Response { return $this->mutate($request, $params, 'simulation.edit', fn (int $id, array $body, array $user) => $this->service->addEvent($id, (int) ($params[1] ?? 0), $body, (int) $user['id'])); }
    public function simulateRound(Request $request, array $params = []): Response { return $this->mutate($request, $params, 'simulation.edit', fn (int $id, array $body, array $user) => $this->service->simulateRound($id, (int) ($body['round_id'] ?? 0), (int) $user['id'])); }

    public function duplicate(Request $request, array $params = []): Response
    {
        $user = $this->authorized($request, 'simulation.create');
        if ($user instanceof Response) return $user;
        if (!$this->validCsrf($request)) return Response::forbidden('Sessao expirada.');
        $scenario = $this->scenario((int) ($params[0] ?? 0));
        if (!$scenario) return Response::forbidden();
        $copy = $this->simulations->duplicate((int) $scenario['id'], (int) $user['id']);
        Session::flash('simulation_message', 'Cenario duplicado.');
        return $this->redirect('/admin/simulacoes/' . $copy);
    }

    public function archive(Request $request, array $params = []): Response { return $this->state($request, $params, 'archive'); }
    public function delete(Request $request, array $params = []): Response { return $this->state($request, $params, 'delete'); }

    private function mutate(Request $request, array $params, string $permission, callable $callback): Response
    {
        $user = $this->authorized($request, $permission);
        if ($user instanceof Response) return $user;
        if (!$this->validCsrf($request)) return Response::forbidden('Sessao expirada.');
        $scenario = $this->scenario((int) ($params[0] ?? 0));
        if (!$scenario) return Response::forbidden();
        $result = $callback((int) $scenario['id'], $request->body, $user);
        Session::flash('simulation_message', $result['ok'] ? 'Cenario atualizado.' : implode(' ', $result['errors']));
        return $this->redirect('/admin/simulacoes/' . $scenario['id']);
    }

    private function state(Request $request, array $params, string $action): Response
    {
        $user = $this->authorized($request, 'simulation.delete');
        if ($user instanceof Response) return $user;
        if (!$this->validCsrf($request)) return Response::forbidden('Sessao expirada.');
        $scenario = $this->scenario((int) ($params[0] ?? 0));
        if (!$scenario) return Response::forbidden();
        if ($action === 'archive') $this->simulations->archive((int) $scenario['id']); else $this->simulations->delete((int) $scenario['id']);
        $this->audit->record('simulation.' . $action, (int) $user['id'], 'simulation_scenario', (int) $scenario['id'], [], $request);
        Session::flash('simulation_message', $action === 'archive' ? 'Cenario arquivado.' : 'Cenario excluido.');
        return $this->redirect('/admin/simulacoes');
    }

    private function formData(): array
    {
        $championships = $this->simulations->championships();
        $phases = [];
        foreach ($championships as $championship) foreach ($this->simulations->phases((int) $championship['id']) as $phase) $phases[] = array_merge($phase, ['championship_id' => $championship['id']]);
        return ['championships' => $championships, 'phases' => $phases];
    }

    private function authorized(Request $request, string $permission): array|Response
    {
        $user = $this->guard($request, $permission);
        if ($user instanceof Response) return $user;
        return $this->administrator($user) ? $user : Response::forbidden();
    }

    private function scenario(int $id): ?array { return $this->simulations->scenario($id); }
    private function redirect(string $path): Response { return Response::redirect(Config::url($path)); }
    private function administrator(array $user): bool { return in_array('administrator', $this->authorization->roleKeys($user), true); }
}
