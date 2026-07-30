<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\MatchOperationRepository;
use App\Repositories\UserRepository;
use App\Services\MatchOperationAccessService;
use App\Services\ScheduleAccessService;

final class MatchOperatorController extends Controller
{
    public function __construct(UserRepository $users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly MatchOperationRepository $operations, private readonly MatchOperationAccessService $operationAccess, private readonly ScheduleAccessService $scheduleAccess)
    { parent::__construct($users, $authorization, $audit); }

    public function mine(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'matches.operate'); if ($user instanceof Response) return $user;
        if ($this->operationAccess->scope($user) !== 'operator') return Response::forbidden();
        return $this->page('Minhas partidas', 'operator/matches', ['user' => $user, 'items' => $this->operations->assignedMatches((int) $user['id'])]);
    }

    public function assignments(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'schedule.update'); if ($user instanceof Response) return $user;
        $match = $this->scheduleAccess->findMatch($user, (int) ($params[0] ?? 0));
        if (!$match || !$this->scheduleAccess->canManage($user, (int) $match['championship_id'])) return Response::forbidden();
        return $this->page('Operadores da partida', 'admin/matches/operators', ['user' => $user, 'match' => $match, 'operators' => $this->users->listByRole('match_operator'), 'assigned' => $this->operations->assignments((int) $match['id']), 'message' => Session::consumeFlash('operator_message')]);
    }

    public function assign(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'schedule.update'); if ($user instanceof Response) return $user;
        $match = $this->scheduleAccess->findMatch($user, (int) ($params[0] ?? 0));
        if (!$match || !$this->scheduleAccess->canManage($user, (int) $match['championship_id'])) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $operator = $this->users->findById((int) ($request->body['operator_id'] ?? 0));
        if (!$operator || !array_filter($this->users->roles((int) $operator['id']), static fn (array $role): bool => $role['key'] === 'match_operator')) return Response::html('Operador invalido.', 422);
        $this->operations->assignOperator((int) $match['id'], (int) $operator['id'], (int) $user['id']);
        $this->audit->record('match_operator.assigned', (int) $user['id'], 'match', (int) $match['id'], ['operator_id' => (int) $operator['id']], $request);
        Session::flash('operator_message', 'Operador atribuido a partida.');
        return Response::redirect(Config::url('/admin/partidas/' . $match['id'] . '/operadores'));
    }
}
