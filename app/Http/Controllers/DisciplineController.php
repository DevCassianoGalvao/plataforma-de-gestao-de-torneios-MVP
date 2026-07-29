<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AthleteRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\DisciplineRepository;
use App\Repositories\TeamRepository;
use App\Repositories\TeamStaffRepository;
use App\Services\DisciplineAccessService;
use App\Services\DisciplineService;

final class DisciplineController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly DisciplineAccessService $access, private readonly DisciplineService $service, private readonly DisciplineRepository $discipline, private readonly ChampionshipRepository $championships, private readonly TeamRepository $teams, private readonly AthleteRepository $athletes, private readonly TeamStaffRepository $staff)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'discipline.view');
        if ($guard instanceof Response) return $guard;
        $options = $this->championships($guard);
        $championshipId = (int) ($request->query['championship_id'] ?? ($options[0]['id'] ?? 0));
        if (!$this->access->canViewChampionship($guard, $championshipId)) return Response::forbidden();
        $scope = $this->access->scope($guard);
        $teamScope = $scope === 'administrator' ? 'administrator' : ($scope === 'organizer' ? 'organizer' : 'team');
        $teams = $this->teams->listForUser((int) $guard['id'], $teamScope, ['championship_id' => $championshipId]);
        $athletes = [];
        $staff = [];
        foreach ($teams as $team) {
            foreach ($this->athletes->listForUser((int) $guard['id'], $teamScope, ['team_id' => $team['id']]) as $athlete) $athletes[] = $athlete;
            foreach ($this->staff->list((int) $team['id'], ['status' => 'active']) as $member) $staff[] = $member + ['team_name' => $team['name'], 'team_id' => $team['id']];
        }
        $data = $this->service->list($championshipId, ['team_id' => $request->query['team_id'] ?? '', 'allowed_team_ids' => $this->access->allowedTeamIds($guard, $championshipId)]);
        return $this->page('Disciplina e suspensoes', 'admin/discipline/index', ['user' => $guard, 'championships' => $options, 'championship_id' => $championshipId, 'teams' => $teams, 'athletes' => $athletes, 'staff' => $staff, 'summary' => $data['summary'], 'suspensions' => $data['suspensions'], 'ledger' => $data['ledger'], 'history' => $data['history'], 'canManage' => $this->access->canManage($guard, $championshipId), 'message' => Session::consumeFlash('discipline_message')]);
    }

    public function manual(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'suspensions.create_manual');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $championshipId = (int) ($request->body['championship_id'] ?? 0);
        if (!$this->access->canManage($guard, $championshipId)) return Response::forbidden();
        $result = $this->service->createManual($guard, array_merge($request->body, ['championship_id' => $championshipId]));
        if (!$result['ok']) return $this->errorPage('Disciplina', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('discipline_message', 'Suspensao manual registrada.');
        return Response::redirect(Config::url('/admin/disciplina?championship_id=' . $championshipId));
    }

    public function revoke(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'suspensions.revoke');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $suspension = $this->discipline->suspension((int) ($params[0] ?? 0));
        if (!$suspension || !$this->access->canManage($guard, (int) $suspension['championship_id'])) return Response::forbidden();
        if (!$this->discipline->revokeSuspension((int) $suspension['id'], (int) $guard['id'], trim((string) ($request->body['reason'] ?? '')))) return $this->errorPage('Disciplina', 'errors/simple', ['message' => 'Suspensao nao pode ser revogada.'], 422);
        Session::flash('discipline_message', 'Suspensao revogada com historico.');
        return Response::redirect(Config::url('/admin/disciplina?championship_id=' . $suspension['championship_id']));
    }

    public function cancelCard(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'cards.cancel');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $event = $this->discipline->event((int) ($params[0] ?? 0));
        if (!$event || !$this->access->canManage($guard, (int) $event['championship_id'])) return Response::forbidden();
        $result = $this->service->cancelCard($guard, (int) $event['id'], (string) ($request->body['reason'] ?? ''));
        if (!$result['ok']) return $this->errorPage('Disciplina', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('discipline_message', 'Cartao anulado com historico.');
        return Response::redirect(Config::url('/admin/disciplina?championship_id=' . $event['championship_id']));
    }

    private function championships(array $user): array
    {
        if ($this->access->scope($user) === 'team') {
            $result = [];
            foreach ($this->teams->listForUser((int) $user['id'], 'team') as $team) $result[(int) $team['championship_id']] = ['id' => (int) $team['championship_id'], 'name' => $team['championship_name']];
            return array_values($result);
        }
        $admin = in_array('administrator', $this->authorization->roleKeys($user), true);
        return $this->championships->listForUser((int) $user['id'], $admin, ['status' => 'published']);
    }
}
