<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\MatchOperationRepository;
use App\Services\MatchOperationAccessService;
use App\Services\MatchOperationService;

final class MatchOperationController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly MatchOperationAccessService $access, private readonly MatchOperationService $service, private readonly MatchOperationRepository $operations)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function show(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'match_operation.view');
        if ($guard instanceof Response) return $guard;
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0));
        if (!$match || !$this->access->canView($guard, $match)) return Response::forbidden();
        return $this->page('Central operacional da partida', 'admin/matches/operation', $this->viewData($guard, $match, []));
    }

    public function event(Request $request, array $params = []): Response
    {
        return $this->mutate($request, $params, 'event', fn (array $user, array $match): array => $this->service->addEvent($user, $match, $this->eventInput($request)));
    }

    public function substitution(Request $request, array $params = []): Response
    {
        return $this->mutate($request, $params, 'substitution', fn (array $user, array $match): array => $this->service->addSubstitution($user, $match, $this->substitutionInput($request)));
    }

    public function officials(Request $request, array $params = []): Response
    {
        return $this->mutate($request, $params, 'officials', fn (array $user, array $match): array => $this->service->saveOfficials($user, $match, (array) $request->body));
    }

    public function times(Request $request, array $params = []): Response
    {
        return $this->mutate($request, $params, 'times', fn (array $user, array $match): array => $this->service->saveTimes($user, $match, (array) $request->body));
    }

    public function administrativeResult(Request $request, array $params = []): Response
    {
        return $this->mutate($request, $params, 'result', fn (array $user, array $match): array => $this->service->saveAdministrativeResult($user, $match, ['home_score' => $request->body['home_score'] ?? null, 'away_score' => $request->body['away_score'] ?? null, 'reason' => $request->body['reason'] ?? '']));
    }

    public function finish(Request $request, array $params = []): Response
    {
        return $this->mutate($request, $params, 'finish', fn (array $user, array $match): array => $this->service->finish($user, $match, ($request->body['confirm_checklist'] ?? '') === 'yes'));
    }

    public function homologate(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'match_operation.homologate');
        if ($guard instanceof Response) return $guard;
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0));
        if (!$match || !$this->access->canHomologate($guard, $match)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->homologate($guard, $match, ($request->body['confirm_homologation'] ?? '') === 'yes');
        if (!$result['ok']) return $this->renderErrors($guard, $match, $result['errors']);
        Session::flash('match_operation_message', 'Partida homologada.');
        return Response::redirect(Config::url('/admin/partidas/' . $match['id'] . '/operacao'));
    }

    public function cancelEvent(Request $request, array $params = []): Response
    {
        return $this->reviewMutate($request, $params, 'match_operation.cancel_event', fn (array $user, array $match): array => $this->service->cancelEvent($user, $match, (int) ($request->body['event_id'] ?? 0), trim((string) ($request->body['reason'] ?? ''))));
    }

    public function review(Request $request, array $params = []): Response
    {
        return $this->reviewMutate($request, $params, 'match_operation.review', fn (array $user, array $match): array => $this->service->review($user, $match, (string) ($request->body['decision'] ?? ''), trim((string) ($request->body['reason'] ?? '')), ($request->body['confirm_review'] ?? '') === 'yes'));
    }

    public function requestRectification(Request $request, array $params = []): Response
    {
        return $this->reviewMutate($request, $params, 'match_operation.rectify', fn (array $user, array $match): array => $this->service->requestRectification($user, $match, trim((string) ($request->body['reason'] ?? ''))));
    }

    public function decideRectification(Request $request, array $params = []): Response
    {
        return $this->reviewMutate($request, $params, 'match_operation.rectify', fn (array $user, array $match): array => $this->service->decideRectification($user, $match, (int) ($request->body['rectification_id'] ?? 0), ($request->body['decision'] ?? '') === 'approve', trim((string) ($request->body['reason'] ?? ''))));
    }

    private function mutate(Request $request, array $params, string $action, callable $callback): Response
    {
        $guard = $this->guard($request, 'match_operation.operate');
        if ($guard instanceof Response) return $guard;
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0));
        if (!$match || !$this->access->canOperate($guard, $match)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $callback($guard, $match);
        if (!$result['ok']) return $this->renderErrors($guard, $match, $result['errors']);
        Session::flash('match_operation_message', 'Registro salvo: ' . $action . '.');
        return Response::redirect(Config::url('/admin/partidas/' . $match['id'] . '/operacao'));
    }

    private function reviewMutate(Request $request, array $params, string $permission, callable $callback): Response
    {
        $guard = $this->guard($request, $permission);
        if ($guard instanceof Response) return $guard;
        $match = $this->access->matchForUser($guard, (int) ($params[0] ?? 0));
        if (!$match || !$this->access->canHomologate($guard, $match)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $callback($guard, $match);
        if (!$result['ok']) return $this->renderErrors($guard, $match, $result['errors']);
        Session::flash('match_operation_message', 'Revisao da partida registrada.');
        return Response::redirect(Config::url('/admin/partidas/' . $match['id'] . '/operacao'));
    }

    private function renderErrors(array $user, array $match, array $errors): Response
    {
        return $this->errorPage('Central operacional', 'admin/matches/operation', array_merge($this->viewData($user, $match, $errors), ['errors' => $errors]), 422);
    }

    private function viewData(array $user, array $match, array $errors): array
    {
        $payload = $this->service->payload($match, (int) $user['id']);
        return array_merge(['user' => $user, 'match' => $match, 'errors' => $errors, 'message' => Session::consumeFlash('match_operation_message'), 'canOperate' => $this->access->canOperate($user, $match), 'canHomologate' => $this->access->canHomologate($user, $match), 'canReview' => $this->authorization->can($user, 'match_operation.review'), 'canRectify' => $this->authorization->can($user, 'match_operation.rectify'), 'canCancelEvent' => $this->authorization->can($user, 'match_operation.cancel_event'), 'operationBase' => Config::url('/admin/partidas/' . $match['id'] . '/operacao')], $payload);
    }

    private function eventInput(Request $request): array
    {
        return ['event_type' => $request->body['event_type'] ?? '', 'period' => $request->body['period'] ?? 'regular', 'team_id' => $request->body['team_id'] ?? '', 'person_type' => $request->body['person_type'] ?? 'athlete', 'athlete_id' => $request->body['athlete_id'] ?? '', 'team_staff_id' => $request->body['team_staff_id'] ?? '', 'related_athlete_id' => $request->body['related_athlete_id'] ?? '', 'minute' => $request->body['minute'] ?? '', 'notes' => trim((string) ($request->body['notes'] ?? ''))];
    }

    private function substitutionInput(Request $request): array
    {
        return ['team_id' => $request->body['team_id'] ?? 0, 'athlete_out_id' => $request->body['athlete_out_id'] ?? 0, 'athlete_in_id' => $request->body['athlete_in_id'] ?? 0, 'period' => $request->body['period'] ?? 'regular', 'window_number' => $request->body['window_number'] ?? '', 'minute' => $request->body['minute'] ?? '', 'notes' => trim((string) ($request->body['notes'] ?? ''))];
    }
}
