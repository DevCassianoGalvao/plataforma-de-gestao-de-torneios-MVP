<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ChampionshipRepository;
use App\Repositories\StandingsRepository;
use App\Services\StandingsAccessService;
use App\Services\StandingsService;

final class StandingsController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly StandingsAccessService $access, private readonly StandingsService $service, private readonly StandingsRepository $standings, private readonly ChampionshipRepository $championships)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'standings.view');
        if ($guard instanceof Response) return $guard;
        $phaseId = (int) ($request->query['phase_id'] ?? 0);
        $phase = $this->standings->phase($phaseId);
        if (!$phase || !$this->access->canViewPhase($guard, $phaseId)) return Response::forbidden();
        $groups = $this->standings->groups($phaseId);
        $table = [];
        foreach ($groups as $group) $table[(int) $group['id']] = $this->standings->standings((int) $group['id']);
        return $this->page('Classificacao', 'admin/standings/index', ['user' => $guard, 'phase' => $phase, 'groups' => $groups, 'table' => $table, 'canRecalculate' => $this->access->canManage($guard, $phaseId, 'standings.recalculate'), 'canGenerate' => $this->access->canManage($guard, $phaseId, 'knockout.generate'), 'message' => Session::consumeFlash('standings_message')]);
    }

    public function recalculate(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'standings.recalculate');
        if ($guard instanceof Response) return $guard;
        $phaseId = (int) ($request->body['phase_id'] ?? 0);
        $phase = $this->standings->phase($phaseId);
        if (!$phase || !$this->access->canManage($guard, $phaseId, 'standings.recalculate')) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $this->service->recalculate($phase, (int) $guard['id']);
        Session::flash('standings_message', 'Classificacao recalculada com sucesso.');
        return Response::redirect(Config::url('/admin/classificacao?phase_id=' . $phaseId));
    }

    public function generateKnockout(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'knockout.generate');
        if ($guard instanceof Response) return $guard;
        $phaseId = (int) ($request->body['phase_id'] ?? 0);
        $phase = $this->standings->phase($phaseId);
        if (!$phase || !$this->access->canManage($guard, $phaseId, 'knockout.generate')) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->generateKnockout($phase, (int) $guard['id']);
        if (!$result['ok']) return $this->errorPage('Classificacao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('standings_message', 'Chave mata-mata gerada.');
        return Response::redirect(Config::url('/admin/mata-mata?phase_id=' . $result['phase']['id']));
    }

    public function knockout(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'standings.view');
        if ($guard instanceof Response) return $guard;
        $phaseId = (int) ($request->query['phase_id'] ?? 0);
        $phase = $this->standings->phase($phaseId);
        if (!$phase || !$this->access->canViewPhase($guard, $phaseId)) return Response::forbidden();
        return $this->page('Chave mata-mata', 'admin/standings/knockout', ['user' => $guard, 'phase' => $phase, 'bracket' => $this->service->bracket($phaseId), 'result' => $this->service->result((int) $phase['championship_id'], $phaseId), 'message' => Session::consumeFlash('standings_message')]);
    }

    public function advance(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'knockout.advance');
        if ($guard instanceof Response) return $guard;
        $match = $this->standings->homologatedMatch((int) ($params[0] ?? 0));
        $tie = $match ? $this->standings->knockoutMatch((int) $match['id']) : null;
        if (!$match || !$tie || !$this->access->canManage($guard, (int) $tie['phase_id'], 'knockout.advance')) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->processKnockoutMatch($match, (int) $guard['id']);
        if (!$result['ok']) return $this->errorPage('Mata-mata', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('standings_message', 'Vencedor avancado na chave.');
        return Response::redirect(Config::url('/admin/mata-mata?phase_id=' . $tie['phase_id']));
    }
}
