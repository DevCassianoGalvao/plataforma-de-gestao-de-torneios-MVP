<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AthleteRepository;
use App\Repositories\TeamRepository;
use App\Services\RegistrationAccessService;
use App\Services\RegistrationRules;
use App\Services\RegistrationService;

final class RegistrationController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly RegistrationAccessService $access, private readonly RegistrationService $service, private readonly AthleteRepository $athletes, private readonly TeamRepository $teams)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'registrations.view');
        if ($guard instanceof Response) return $guard;
        $filters = ['status' => (string) ($request->query['status'] ?? ''), 'championship_id' => (string) ($request->query['championship_id'] ?? ''), 'team_id' => (string) ($request->query['team_id'] ?? ''), 'athlete_id' => (string) ($request->query['athlete_id'] ?? '')];
        return $this->page('Inscricoes', 'admin/registrations/index', ['user' => $guard, 'items' => $this->access->list($guard, $filters), 'query' => $filters, 'statuses' => RegistrationRules::STATUSES, 'championships' => $this->access->authorizedChampionships($guard), 'teams' => $this->availableTeams($guard), 'canCreate' => $this->authorization->can($guard, 'registrations.create'), 'message' => Session::consumeFlash('registration_message')]);
    }

    public function createForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'registrations.create');
        if ($guard instanceof Response) return $guard;
        $record = [
            'athlete_id' => (int) ($request->query['athlete_id'] ?? 0),
            'team_id' => (int) ($request->query['team_id'] ?? 0),
        ];
        foreach ($this->availableTeams($guard) as $team) {
            if ((int) $team['id'] === $record['team_id']) {
                $record['championship_id'] = (int) $team['championship_id'];
                break;
            }
        }
        return $this->formPage($guard, $record, [], []);
    }

    public function create(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'registrations.create');
        if ($guard instanceof Response) return $guard;
        $data = $this->input($request);
        if (!$this->validCsrf($request)) return $this->formError($guard, $data, ['A sessao expirou.'], 419);
        if ($this->access->scope($guard) === 'team') {
            $team = $this->access->team($guard, (int) $data['team_id']);
            $athlete = $this->athletes->findForUser((int) $data['athlete_id'], (int) $guard['id'], 'team', true);
            if (!$team || (int) $team['championship_id'] !== (int) $data['championship_id'] || !$athlete || (int) $athlete['team_id'] !== (int) $data['team_id']) return Response::forbidden();
        }
        $result = $this->service->createSubmitted((int) $guard['id'], (int) $data['championship_id'], (int) $data['team_id'], (int) $data['athlete_id'], $data['requested_number'], $data['observations'], $request);
        if (!$result['ok']) return $this->formError($guard, $data, $result['errors'], 422);
        Session::flash('registration_message', 'Inscricao enviada para analise.');
        return Response::redirect(Config::url('/admin/inscricoes/' . $result['id']));
    }

    public function show(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'registrations.view');
        if ($guard instanceof Response) return $guard;
        $registration = $this->access->find($guard, (int) ($params[0] ?? 0));
        if (!$registration) return Response::forbidden();
        return $this->page('Inscricao', 'admin/registrations/show', ['user' => $guard, 'item' => $registration, 'history' => $this->serviceHistory((int) $registration['id']), 'canCorrect' => $this->canCorrect($guard, $registration), 'canReview' => $this->canReview($guard, $registration), 'message' => Session::consumeFlash('registration_message')]);
    }

    public function update(Request $request, array $params = []): Response
    {
        [$guard, $registration] = $this->context($request, (int) ($params[0] ?? 0), 'registrations.update');
        if ($guard instanceof Response) return $guard;
        if (!$this->canCorrect($guard, $registration)) return Response::forbidden();
        $data = $this->input($request);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->updateDraft($registration, (int) $guard['id'], $data['requested_number'], $data['observations'], $request);
        if (!$result['ok']) return $this->errorPage('Inscricao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('registration_message', 'Dados da inscricao corrigidos.');
        return Response::redirect(Config::url('/admin/inscricoes/' . $registration['id']));
    }

    public function submit(Request $request, array $params = []): Response
    {
        [$guard, $registration] = $this->context($request, (int) ($params[0] ?? 0), 'registrations.submit');
        if ($guard instanceof Response) return $guard;
        if (!$this->canCorrect($guard, $registration)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->submit($registration, (int) $guard['id'], $request);
        if (!$result['ok']) return $this->errorPage('Inscricao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('registration_message', 'Inscricao enviada para analise.');
        return Response::redirect(Config::url('/admin/inscricoes/' . $registration['id']));
    }

    public function startReview(Request $request, array $params = []): Response
    {
        return $this->reviewAction($request, $params, 'under_review', null);
    }

    public function requestCorrection(Request $request, array $params = []): Response
    {
        return $this->reviewAction($request, $params, 'pending_correction', (string) ($request->body['pending_issues'] ?? ''));
    }

    public function approve(Request $request, array $params = []): Response
    {
        [$guard, $registration] = $this->reviewContext($request, (int) ($params[0] ?? 0));
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->approve($registration, (int) $guard['id'], $request);
        if (!$result['ok']) return $this->errorPage('Inscricao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('registration_message', 'Inscricao aprovada e incluida no elenco oficial.');
        return Response::redirect(Config::url('/admin/inscricoes/' . $registration['id']));
    }

    public function reject(Request $request, array $params = []): Response
    {
        [$guard, $registration] = $this->reviewContext($request, (int) ($params[0] ?? 0));
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->reject($registration, (int) $guard['id'], (string) ($request->body['rejection_reason'] ?? ''), $request);
        if (!$result['ok']) return $this->errorPage('Inscricao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('registration_message', 'Inscricao rejeitada.');
        return Response::redirect(Config::url('/admin/inscricoes/' . $registration['id']));
    }

    public function cancel(Request $request, array $params = []): Response
    {
        [$guard, $registration] = $this->context($request, (int) ($params[0] ?? 0), 'registrations.cancel');
        if ($guard instanceof Response) return $guard;
        if (!$this->canCorrect($guard, $registration) && !$this->canReview($guard, $registration)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->service->cancel($registration, (int) $guard['id'], $request);
        if (!$result['ok']) return $this->errorPage('Inscricao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('registration_message', 'Inscricao cancelada.');
        return Response::redirect(Config::url('/admin/inscricoes/' . $registration['id']));
    }

    public function roster(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'rosters.view');
        if ($guard instanceof Response) return $guard;
        $filters = ['championship_id' => (string) ($request->query['championship_id'] ?? ''), 'team_id' => (string) ($request->query['team_id'] ?? '')];
        $items = $this->access->list($guard, ['status' => 'approved', ...$filters]);
        return $this->page('Elenco oficial', 'admin/registrations/roster', ['user' => $guard, 'items' => $items, 'filters' => $filters, 'championships' => $this->access->authorizedChampionships($guard), 'teams' => $this->availableTeams($guard)]);
    }

    private function reviewAction(Request $request, array $params, string $to, ?string $notes): Response
    {
        [$guard, $registration] = $this->reviewContext($request, (int) ($params[0] ?? 0));
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $to === 'under_review' ? $this->service->startReview($registration, (int) $guard['id'], $request) : $this->service->requestCorrection($registration, (int) $guard['id'], $notes ?? '', $request);
        if (!$result['ok']) return $this->errorPage('Inscricao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        Session::flash('registration_message', $to === 'under_review' ? 'Inscricao assumida para analise.' : 'Correcao solicitada ao treinador.');
        return Response::redirect(Config::url('/admin/inscricoes/' . $registration['id']));
    }

    private function reviewContext(Request $request, int $id): array
    {
        [$guard, $registration] = $this->context($request, $id, 'registrations.review');
        if ($guard instanceof Response) return [$guard, null];
        if (!$this->canReview($guard, $registration)) return [Response::forbidden(), null];
        return [$guard, $registration];
    }

    private function context(Request $request, int $id, string $permission): array
    {
        $guard = $this->guard($request, 'registrations.view');
        if ($guard instanceof Response) return [$guard, null];
        if ($this->authorization->cannot($guard, $permission)) return [Response::forbidden(), null];
        $registration = $this->access->find($guard, $id);
        if (!$registration) return [Response::forbidden(), null];
        return [$guard, $registration];
    }

    private function formPage(array $user, array $record, array $errors, array $data = []): Response
    {
        $record = array_merge($record, $data);
        return $this->page('Nova inscricao', 'admin/registrations/form', ['user' => $user, 'record' => $record, 'errors' => $errors, 'championships' => $this->formChampionships($user), 'teams' => $this->availableTeams($user), 'athletes' => $this->availableAthletes($user, (int) ($record['team_id'] ?? 0))]);
    }

    private function formError(array $user, array $data, array $errors, int $status): Response
    {
        return $this->errorPage('Nova inscricao', 'admin/registrations/form', ['user' => $user, 'record' => $data, 'errors' => $errors, 'championships' => $this->formChampionships($user), 'teams' => $this->availableTeams($user), 'athletes' => $this->availableAthletes($user, (int) ($data['team_id'] ?? 0))], $status);
    }

    private function input(Request $request): array
    {
        return ['championship_id' => (int) ($request->body['championship_id'] ?? 0), 'team_id' => (int) ($request->body['team_id'] ?? 0), 'athlete_id' => (int) ($request->body['athlete_id'] ?? 0), 'requested_number' => ($request->body['requested_number'] ?? '') === '' ? null : (int) $request->body['requested_number'], 'observations' => trim((string) ($request->body['observations'] ?? ''))];
    }

    private function availableTeams(array $user): array
    {
        $scope = $this->access->scope($user);
        return $this->teams->listForUser((int) $user['id'], $scope, ['status' => 'active']);
    }

    private function availableAthletes(array $user, int $teamId): array
    {
        $filters = ['status' => 'active'];
        if ($teamId) $filters['team_id'] = $teamId;
        return $this->athletes->listForUser((int) $user['id'], $this->access->scope($user), $filters);
    }

    private function formChampionships(array $user): array
    {
        $championships = $this->access->authorizedChampionships($user);
        if ($championships !== []) return $championships;
        $unique = [];
        foreach ($this->availableTeams($user) as $team) $unique[(int) $team['championship_id']] = ['id' => (int) $team['championship_id'], 'name' => $team['championship_name']];
        return array_values($unique);
    }

    private function serviceHistory(int $id): array
    {
        return $this->service->history($id);
    }

    private function canCorrect(array $user, array $registration): bool
    {
        return in_array($registration['status'], ['draft', 'pending_correction'], true) && $this->authorization->can($user, $this->access->scope($user) === 'team' ? 'registrations.manage_own' : 'registrations.update');
    }

    private function canReview(array $user, array $registration): bool
    {
        return $this->access->scope($user) !== 'team' && $this->authorization->can($user, 'registrations.review');
    }
}
