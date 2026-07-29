<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\LineupRepository;
use App\Repositories\TacticalFormationRepository;
use App\Services\LineupAccessService;
use App\Services\LineupService;

final class LineupController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly LineupAccessService $access, private readonly LineupService $service, private readonly LineupRepository $lineups, private readonly TacticalFormationRepository $formations)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function match(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'lineups.view');
        if ($guard instanceof Response) return $guard;
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0));
        if (!$match) return Response::forbidden();
        $items = [];
        foreach ($this->lineups->listForMatch((int) $match['id']) as $item) {
            $teamId = (int) $item['team_id'];
            if ($item['status'] === 'confirmed' || $this->access->canManageTeam($guard, $match, $teamId)) $items[] = $item;
        }
        return $this->page('Escalacoes da partida', 'admin/lineups/match', ['user' => $guard, 'match' => $match, 'items' => $items, 'homeCanManage' => $this->access->canManageTeam($guard, $match, (int) $match['home_team_id']), 'awayCanManage' => $this->access->canManageTeam($guard, $match, (int) $match['away_team_id']), 'message' => Session::consumeFlash('lineup_message')]);
    }

    public function edit(Request $request, array $params = []): Response
    {
        [$guard, $match, $teamId] = $this->context($request, $params, 'lineups.view');
        if ($guard instanceof Response) return $guard;
        if (!$this->access->canManageTeam($guard, $match, $teamId)) return Response::forbidden();
        try { $lineup = $this->service->ensureDraft($guard, $match, $teamId); } catch (\Throwable $exception) { return $this->errorPage('Escalacao', 'errors/simple', ['message' => $exception->getMessage()], 422); }
        $formData = $this->service->formData($lineup);
        if (($lineup['players'] ?? []) === []) $formData = array_merge($formData, $this->service->suggest($match, $teamId, (int) $lineup['tactical_formation_id'])); 
        return $this->editPage($guard, $match, $teamId, $lineup, $formData, []);
    }

    public function automatic(Request $request, array $params = []): Response
    {
        [$guard, $match, $teamId] = $this->context($request, $params, 'lineups.update');
        if ($guard instanceof Response) return $guard;
        if (!$this->access->canManageTeam($guard, $match, $teamId)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $lineup = $this->lineups->find((int) $match['id'], $teamId) ?? $this->service->ensureDraft($guard, $match, $teamId);
        $formationId = (int) ($request->body['formation_id'] ?? $lineup['tactical_formation_id']);
        $suggestion = $this->service->suggest($match, $teamId, $formationId);
        if (!$suggestion['ok']) return $this->errorPage('Escalacao', 'errors/simple', ['message' => implode(' ', $suggestion['errors'])], 422);
        return $this->editPage($guard, $match, $teamId, $lineup, array_merge(['formation_id' => $formationId], $suggestion), ['Distribuicao sugerida. Revise antes de salvar.']);
    }

    public function save(Request $request, array $params = []): Response
    {
        [$guard, $match, $teamId] = $this->context($request, $params, 'lineups.update');
        if ($guard instanceof Response) return $guard;
        if (!$this->access->canManageTeam($guard, $match, $teamId)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $lineup = $this->lineups->find((int) $match['id'], $teamId) ?? $this->service->ensureDraft($guard, $match, $teamId);
        $data = $this->input($request);
        $confirm = ($request->body['action'] ?? 'save') === 'confirm';
        if ($confirm && !$this->access->canConfirm($guard, $match, $teamId)) return Response::forbidden();
        $result = $this->service->save($guard, $match, $lineup, $data, $confirm);
        if (!$result['ok']) return $this->editPage($guard, $match, $teamId, $lineup, $data, $result['errors'], 422);
        Session::flash('lineup_message', $confirm ? 'Escalacao confirmada.' : 'Rascunho salvo.');
        return Response::redirect(Config::url('/admin/partidas/' . $match['id'] . '/escalacao/' . $teamId));
    }

    public function reopen(Request $request, array $params = []): Response
    {
        [$guard, $match, $teamId] = $this->context($request, $params, 'lineups.reopen');
        if ($guard instanceof Response) return $guard;
        if (!$this->access->canReopen($guard, $match, $teamId)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $lineup = $this->lineups->find((int) $match['id'], $teamId);
        if (!$lineup) return Response::html('Escalacao nao encontrada.', 404);
        $result = $this->service->reopen($guard, $match, $lineup, trim((string) ($request->body['reason'] ?? '')));
        if (!$result['ok']) return $this->errorPage('Escalacao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('lineup_message', 'Escalacao reaberta com motivo registrado.');
        return Response::redirect(Config::url('/admin/partidas/' . $match['id'] . '/escalacao/' . $teamId));
    }

    private function context(Request $request, array $params, string $permission): array
    {
        $guard = $this->guard($request, $permission);
        if ($guard instanceof Response) return [$guard, null, 0];
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0));
        $teamId = (int) ($params[1] ?? 0);
        if (!$match || !in_array($teamId, [(int) $match['home_team_id'], (int) $match['away_team_id']], true)) return [Response::forbidden(), null, 0];
        return [$guard, $match, $teamId];
    }

    private function editPage(array $user, array $match, int $teamId, array $lineup, array $formData, array $errors, int $status = 200): Response
    {
        return $this->errorPage('Escalacao de ' . $lineup['team_name'], 'admin/lineups/edit', ['user' => $user, 'match' => $match, 'teamId' => $teamId, 'lineup' => $lineup, 'formData' => $formData, 'formations' => $this->formations->listActive(), 'formation' => $this->formations->findWithSlots((int) ($formData['formation_id'] ?? $lineup['tactical_formation_id'])), 'athletes' => $this->lineups->eligibleAthletes((int) $match['championship_id'], $teamId), 'staff' => $this->lineups->staff($teamId), 'history' => $this->lineups->history((int) $lineup['id']), 'canReopen' => $this->access->canReopen($user, $match, $teamId), 'errors' => $errors, 'message' => Session::consumeFlash('lineup_message')], $status);
    }

    private function input(Request $request): array
    {
        return ['formation_id' => (int) ($request->body['formation_id'] ?? 0), 'starters' => array_map('intval', (array) ($request->body['starters'] ?? [])), 'reserves' => array_values(array_filter(array_map('intval', (array) ($request->body['reserves'] ?? [])))), 'captain_athlete_id' => (int) ($request->body['captain_athlete_id'] ?? 0), 'goalkeeper_athlete_id' => (int) ($request->body['goalkeeper_athlete_id'] ?? 0), 'staff_ids' => array_values(array_filter(array_map('intval', (array) ($request->body['staff_ids'] ?? []))))];
    }
}
