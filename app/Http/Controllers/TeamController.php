<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ChampionshipRepository;
use App\Repositories\StaffRoleRepository;
use App\Repositories\TeamRepository;
use App\Repositories\TeamStaffRepository;
use App\Repositories\RegistrationRepository;
use App\Repositories\TacticalFormationRepository;
use App\Services\AuditService;
use App\Services\ColorRules;
use App\Services\Slugger;
use App\Services\StorageService;
use App\Services\TacticalFormationService;
use App\Services\TeamAccessService;
use App\Services\TeamRules;
use App\Services\TeamStatusService;

final class TeamController extends Controller
{
    public function __construct(
        $users,
        \App\Services\AuthorizationService $authorization,
        AuditService $audit,
        private readonly TeamRepository $teams,
        private readonly TeamStaffRepository $staff,
        private readonly StaffRoleRepository $staffRoles,
        private readonly TeamAccessService $access,
        private readonly TeamStatusService $statusService,
        private readonly TacticalFormationService $formations,
        private readonly ChampionshipRepository $championships,
        private readonly StorageService $storage,
        private readonly RegistrationRepository $registrations,
    ) {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'teams.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Equipes', 'admin/teams/index', [
            'user' => $guard,
            'items' => $this->teams->listForUser((int) $guard['id'], $this->access->scope($guard), $request->query),
            'championships' => $this->access->authorizedChampionships($guard),
            'query' => $request->query,
            'canCreate' => $this->authorization->can($guard, 'teams.create'),
            'message' => Session::consumeFlash('team_message'),
        ]);
    }

    public function createForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'teams.create');
        if ($guard instanceof Response) return $guard;
        return $this->page('Nova equipe', 'admin/teams/form', [
            'user' => $guard,
            'record' => $this->blank(),
            'championships' => $this->access->authorizedChampionships($guard),
            'formations' => $this->formations->list(),
            'errors' => [],
            'editing' => false,
        ]);
    }

    public function create(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'teams.create');
        if ($guard instanceof Response) return $guard;
        $data = $this->teamData($request, 'draft');
        if (!$this->validCsrf($request)) return $this->teamError('Nova equipe', $data, $guard, ['A sessao expirou.'], 419);
        $errors = TeamRules::validate($data);
        $championship = $this->championships->findForUser((int) $data['championship_id'], (int) $guard['id'], $this->access->scope($guard) === 'administrator');
        if (!$championship || $championship['status'] === 'archived') $errors[] = 'Escolha um campeonato autorizado e ativo.';
        if ($data['default_tactical_formation_id'] && !$this->formations->find((int) $data['default_tactical_formation_id'])) $errors[] = 'Escolha uma formacao valida.';
        if ($errors) return $this->teamError('Nova equipe', $data, $guard, $errors, 422);
        try {
            $id = $this->teams->create($data, (int) $guard['id']);
        } catch (\PDOException) {
            return $this->teamError('Nova equipe', $data, $guard, ['Ja existe uma equipe com esse nome ou slug neste campeonato.'], 422);
        }
        $this->audit->record('teams.created', (int) $guard['id'], 'team', $id, ['championship_id' => (int) $data['championship_id']], $request);
        Session::flash('team_message', 'Equipe criada como rascunho.');
        return Response::redirect(Config::url('/admin/equipes/' . $data['slug']));
    }

    public function show(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->context($request, (string) ($params[0] ?? ''), 'teams.view');
        if ($guard instanceof Response) return $guard;
        $formation = !empty($team['default_tactical_formation_id']) ? $this->formations->find((int) $team['default_tactical_formation_id']) : null;
        return $this->page((string) $team['name'], 'admin/teams/show', [
            'user' => $guard,
            'team' => $team,
            'assignments' => $this->teams->assignments((int) $team['id']),
            'staff' => $this->staff->list((int) $team['id']),
            'formation' => $formation,
            'officialRoster' => $this->registrations->officialRoster((int) $team['championship_id'], (int) $team['id']),
            'canEdit' => $this->access->findForEitherMutation($guard, (int) $team['id'], 'teams.update', 'teams.manage_own') !== null,
            'canAssignments' => $this->access->canManageAssignments($guard, (int) $team['id']),
            'canStaff' => $this->canStaff($guard, 'team_staff.update', (int) $team['id']),
            'canFormation' => $this->canFormation($guard, (int) $team['id']),
            'canStatus' => $this->access->scope($guard) !== 'team' && $this->authorization->can($guard, 'teams.deactivate'),
            'canRestore' => $this->access->scope($guard) !== 'team' && $this->authorization->can($guard, 'teams.restore'),
            'canIdentity' => $this->access->find($guard, (int) $team['id'], 'teams.manage_identity', true) !== null,
            'message' => Session::consumeFlash('team_message'),
        ]);
    }

    public function editForm(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->mutationContext($request, (string) ($params[0] ?? ''));
        if ($guard instanceof Response) return $guard;
        return $this->page('Editar equipe', 'admin/teams/form', [
            'user' => $guard,
            'record' => $team,
            'championships' => [],
            'formations' => $this->formations->list(),
            'errors' => [],
            'editing' => true,
        ]);
    }

    public function update(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->mutationContext($request, (string) ($params[0] ?? ''));
        if ($guard instanceof Response) return $guard;
        $data = $this->teamData($request, (string) $team['status']);
        if (!$this->validCsrf($request)) return $this->teamError('Editar equipe', array_merge($team, $data), $guard, ['A sessao expirou.'], 419, true);
        $data['championship_id'] = $team['championship_id'];
        $errors = TeamRules::validate($data);
        if ($errors) return $this->teamError('Editar equipe', array_merge($team, $data), $guard, $errors, 422, true);
        try {
            $this->teams->updateGeneral((int) $team['id'], $data);
        } catch (\PDOException) {
            return $this->teamError('Editar equipe', array_merge($team, $data), $guard, ['Ja existe uma equipe com esse nome ou slug neste campeonato.'], 422, true);
        }
        $this->audit->record('teams.updated', (int) $guard['id'], 'team', (int) $team['id'], [], $request);
        Session::flash('team_message', 'Dados da equipe atualizados.');
        return Response::redirect(Config::url('/admin/equipes/' . $data['slug']));
    }

    public function identityForm(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->context($request, (string) ($params[0] ?? ''), 'teams.manage_identity', true);
        if ($guard instanceof Response) return $guard;
        return $this->page('Identidade da equipe', 'admin/teams/identity', ['user' => $guard, 'team' => $team, 'errors' => []]);
    }

    public function identity(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->context($request, (string) ($params[0] ?? ''), 'teams.manage_identity', true);
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $data = ['primary_color' => (string) ($request->body['primary_color'] ?? ''), 'secondary_color' => (string) ($request->body['secondary_color'] ?? ''), 'shield_path' => $team['shield_path']];
        $errors = [];
        $stored = null;
        foreach (['primary_color', 'secondary_color'] as $color) if (!ColorRules::valid($data[$color])) $errors[] = 'Use cores no formato #RRGGBB.';
        $file = $request->files['shield'] ?? [];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $stored = $this->storage->storeOptimizedImage($file, 'teams/' . $team['id'], ['max_width' => 1200, 'max_height' => 1200, 'quality' => 86]);
                $data['shield_path'] = $stored['path'];
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        if ($errors) {
            if ($stored) $this->storage->delete($stored['path']);
            return $this->errorPage('Identidade da equipe', 'admin/teams/identity', ['user' => $guard, 'team' => array_merge($team, $data), 'errors' => $errors], 422);
        }
        $this->teams->updateIdentity((int) $team['id'], $data);
        if ($team['shield_path'] && $team['shield_path'] !== $data['shield_path']) $this->storage->delete((string) $team['shield_path']);
        $this->audit->record('teams.identity_updated', (int) $guard['id'], 'team', (int) $team['id'], ['shield_changed' => $team['shield_path'] !== $data['shield_path']], $request);
        Session::flash('team_message', 'Identidade da equipe atualizada.');
        return Response::redirect(Config::url('/admin/equipes/' . $team['slug']));
    }

    public function asset(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->context($request, (string) ($params[0] ?? ''), 'teams.view');
        if ($guard instanceof Response) return $guard;
        if ((string) ($params[1] ?? '') !== 'shield_path') return Response::html('Arquivo nao encontrado.', 404);
        $file = $this->storage->read((string) $team['shield_path']);
        if (!$file) return Response::html('Arquivo nao encontrado.', 404);
        return Response::binary($file['body'], $file['mime'], $file['name']);
    }

    public function status(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) return $guard;
        $next = (string) ($request->body['status'] ?? '');
        $permission = $next === 'active' ? 'teams.restore' : 'teams.deactivate';
        $team = ctype_digit((string) ($params[0] ?? '')) ? $this->access->find($guard, (int) $params[0], $permission, true) : $this->findBySlug($guard, (string) $params[0], $permission, true);
        if (!$team) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->statusService->transition($team, $next);
        if (!$result['ok']) return $this->errorPage('Status da equipe', 'errors/simple', ['message' => $result['message']], 422);
        $this->teams->updateStatus((int) $team['id'], $next);
        $this->audit->record('teams.status_changed', (int) $guard['id'], 'team', (int) $team['id'], ['previous' => $result['previous'], 'next' => $next, 'reason' => trim((string) ($request->body['reason'] ?? ''))], $request);
        Session::flash('team_message', 'Status da equipe atualizado.');
        return Response::redirect(Config::url('/admin/equipes/' . $team['slug']));
    }

    public function restore(Request $request, array $params = []): Response
    {
        $body = $request->body;
        $body['status'] = 'active';
        return $this->status(Request::fake('POST', $request->path, $body, $request->files), $params);
    }

    public function assignments(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->context($request, (string) ($params[0] ?? ''), 'teams.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Responsaveis da equipe', 'admin/teams/assignments', ['user' => $guard, 'team' => $team, 'assignments' => $this->teams->assignments((int) $team['id']), 'candidates' => $this->users->listByRole('team_manager'), 'canManage' => $this->access->canManageAssignments($guard, (int) $team['id']), 'errors' => []]);
    }

    public function assign(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'teams.manage_assignments');
        if ($guard instanceof Response) return $guard;
        $team = ctype_digit((string) ($params[0] ?? '')) ? $this->access->find($guard, (int) $params[0], 'teams.manage_assignments', true) : $this->findBySlug($guard, (string) $params[0], 'teams.manage_assignments', true);
        if (!$team) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $userId = (int) ($request->body['user_id'] ?? 0);
        $type = (string) ($request->body['assignment_type'] ?? '');
        $candidate = $this->users->findById($userId);
        $errors = [];
        if (!in_array($type, ['manager', 'head_coach', 'assistant_coach', 'viewer'], true)) $errors[] = 'Tipo de vinculo invalido.';
        if (!$candidate || $candidate['status'] !== 'active' || !in_array('team_manager', $this->authorization->roleKeys($candidate), true)) $errors[] = 'Escolha um usuario ativo com perfil de treinador ou gestor.';
        if ($this->teams->activeAssignmentExists((int) $team['id'], $userId, $type)) $errors[] = 'Este usuario ja possui esse vinculo ativo.';
        if ($errors) return $this->assignmentError($guard, $team, $errors);
        try {
            $assignmentId = $this->teams->createAssignment((int) $team['id'], $userId, $type, (int) $guard['id'], (string) (($request->body['starts_at'] ?? '') ?: date('Y-m-d')));
        } catch (\PDOException) {
            return $this->assignmentError($guard, $team, ['Este vinculo ja existe no historico para essa data.']);
        }
        $this->audit->record('teams.assignment_created', (int) $guard['id'], 'team_user_assignment', $assignmentId, ['team_id' => (int) $team['id'], 'user_id' => $userId, 'assignment_type' => $type], $request);
        Session::flash('team_message', 'Responsavel vinculado.');
        return Response::redirect(Config::url('/admin/equipes/' . $team['slug'] . '/responsaveis'));
    }

    public function endAssignment(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'teams.manage_assignments');
        if ($guard instanceof Response) return $guard;
        $team = ctype_digit((string) ($params[0] ?? '')) ? $this->access->find($guard, (int) $params[0], 'teams.manage_assignments', true) : $this->findBySlug($guard, (string) $params[0], 'teams.manage_assignments', true);
        $assignment = $this->teams->assignment((int) ($params[1] ?? 0));
        if (!$team || !$assignment || (int) $assignment['team_id'] !== (int) $team['id']) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $this->teams->endAssignment((int) $assignment['id'], (string) (($request->body['ends_at'] ?? '') ?: date('Y-m-d')));
        $this->audit->record('teams.assignment_ended', (int) $guard['id'], 'team_user_assignment', (int) $assignment['id'], ['team_id' => (int) $team['id'], 'user_id' => (int) $assignment['user_id']], $request);
        return Response::redirect(Config::url('/admin/equipes/' . $team['slug'] . '/responsaveis'));
    }

    public function staff(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->context($request, (string) ($params[0] ?? ''), 'team_staff.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Comissao tecnica', 'admin/teams/staff', ['user' => $guard, 'team' => $team, 'items' => $this->staff->list((int) $team['id'], $request->query), 'roles' => $this->staffRoles->listActive(), 'query' => $request->query, 'canManage' => $this->canStaff($guard, 'team_staff.create', (int) $team['id'])]);
    }

    public function staffForm(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->staffMutationContext($request, (string) ($params[0] ?? ''));
        if ($guard instanceof Response) return $guard;
        $record = isset($params[1]) ? $this->staff->find((int) $params[1]) : $this->blankStaff();
        if (!$record || (isset($params[1]) && (int) $record['team_id'] !== (int) $team['id'])) return Response::html('Membro nao encontrado.', 404);
        return $this->page('Comissao tecnica', 'admin/teams/staff-form', ['user' => $guard, 'team' => $team, 'record' => $record, 'roles' => $this->staffRoles->listActive(), 'users' => $this->users->listByRole('team_manager'), 'editing' => isset($params[1]), 'errors' => []]);
    }

    public function saveStaff(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->staffMutationContext($request, (string) ($params[0] ?? ''));
        if ($guard instanceof Response) return $guard;
        $editing = isset($params[1]);
        $record = $editing ? $this->staff->find((int) $params[1]) : null;
        if ($editing && (!$record || (int) $record['team_id'] !== (int) $team['id'])) return Response::html('Membro nao encontrado.', 404);
        $data = $this->staffData($request, $record['photo_path'] ?? null);
        if (!$this->validCsrf($request)) return $this->staffError($guard, $team, $data, ['A sessao expirou.'], $editing);
        $errors = TeamRules::validateStaff($data);
        if (!$this->staffRoles->find((int) $data['staff_role_id'])) $errors[] = 'Escolha uma funcao valida.';
        if ($data['user_id']) {
            $candidate = $this->users->findById((int) $data['user_id']);
            if (!$candidate || $candidate['status'] !== 'active' || !in_array('team_manager', $this->authorization->roleKeys($candidate), true)) $errors[] = 'O usuario vinculado deve estar ativo e possuir perfil de treinador ou gestor.';
        }
        $file = $request->files['photo'] ?? [];
        $stored = null;
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $stored = $this->storage->storeOptimizedImage($file, 'team-staff/' . $team['id'], ['max_width' => 1200, 'max_height' => 1200]);
                $data['photo_path'] = $stored['path'];
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        if ($errors) {
            if ($stored) $this->storage->delete($stored['path']);
            return $this->staffError($guard, $team, $data, $errors, $editing);
        }
        try {
            $id = $editing ? (int) $record['id'] : $this->staff->create((int) $team['id'], $data);
            if ($editing) $this->staff->update($id, $data);
        } catch (\PDOException) {
            if ($stored) $this->storage->delete($stored['path']);
            return $this->staffError($guard, $team, $data, ['Ja existe um membro com esse nome nesta equipe.'], $editing);
        }
        if ($editing && $record['photo_path'] && $record['photo_path'] !== $data['photo_path']) $this->storage->delete((string) $record['photo_path']);
        $this->audit->record($editing ? 'team_staff.updated' : 'team_staff.created', (int) $guard['id'], 'team_staff', $id, ['team_id' => (int) $team['id']], $request);
        Session::flash('team_message', $editing ? 'Membro atualizado.' : 'Membro cadastrado.');
        return Response::redirect(Config::url('/admin/equipes/' . $team['slug'] . '/comissao'));
    }

    public function staffStatus(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->staffMutationContext($request, (string) ($params[0] ?? ''));
        if ($guard instanceof Response) return $guard;
        $record = $this->staff->find((int) ($params[1] ?? 0));
        if (!$record || (int) $record['team_id'] !== (int) $team['id']) return Response::html('Membro nao encontrado.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $next = (string) ($request->body['status'] ?? '');
        if (!in_array($next, ['active', 'inactive', 'ended'], true)) return Response::html('Status invalido.', 422);
        $this->staff->updateStatus((int) $record['id'], $next);
        $this->audit->record('team_staff.status_changed', (int) $guard['id'], 'team_staff', (int) $record['id'], ['team_id' => (int) $team['id'], 'previous' => $record['status'], 'next' => $next], $request);
        return Response::redirect(Config::url('/admin/equipes/' . $team['slug'] . '/comissao'));
    }

    public function formation(Request $request, array $params = []): Response
    {
        [$guard, $team] = $this->context($request, (string) ($params[0] ?? ''), 'tactical_formations.view');
        if ($guard instanceof Response) return $guard;
        $selected = !empty($team['default_tactical_formation_id']) ? $this->formations->find((int) $team['default_tactical_formation_id']) : null;
        return $this->page('Formacao padrao', 'admin/teams/formation', ['user' => $guard, 'team' => $team, 'formations' => $this->formations->list(), 'selected' => $selected, 'canManage' => $this->canFormation($guard, (int) $team['id'])]);
    }

    public function saveFormation(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'teams.select_default_formation');
        if ($guard instanceof Response) return $guard;
        $team = ctype_digit((string) ($params[0] ?? '')) ? $this->access->find($guard, (int) $params[0], 'teams.select_default_formation', true) : $this->findBySlug($guard, (string) $params[0], 'teams.select_default_formation', true);
        if (!$team) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->formations->setDefault((int) $team['id'], (int) ($request->body['formation_id'] ?? 0), (int) $guard['id']);
        if (!$result['ok']) return $this->errorPage('Formacao padrao', 'errors/simple', ['message' => implode(' ', $result['errors'])], 422);
        $this->audit->record('teams.default_formation_changed', (int) $guard['id'], 'team', (int) $team['id'], ['formation_id' => (int) $request->body['formation_id']], $request);
        Session::flash('team_message', 'Formacao padrao atualizada.');
        return Response::redirect(Config::url('/admin/equipes/' . $team['slug'] . '/formacao'));
    }

    private function context(Request $request, string $value, string $permission, bool $mutation = false): array
    {
        $guard = $this->guard($request, $permission);
        if ($guard instanceof Response) return [$guard, null];
        $team = ctype_digit($value) ? $this->access->find($guard, (int) $value, $permission, $mutation) : $this->findBySlug($guard, $value, $permission, $mutation);
        if (!$team) return [Response::forbidden(), null];
        return [$guard, $team];
    }

    private function mutationContext(Request $request, string $value): array
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) return [$guard, null];
        $team = ctype_digit($value) ? $this->access->findForEitherMutation($guard, (int) $value, 'teams.update', 'teams.manage_own') : $this->findBySlugForMutation($guard, $value);
        if (!$team) return [Response::forbidden(), null];
        return [$guard, $team];
    }

    private function findBySlug(array $user, string $slug, string $permission, bool $mutation): ?array
    {
        if ($this->authorization->cannot($user, $permission)) return null;
        $team = $this->teams->findForUserBySlug($slug, (int) $user['id'], $this->access->scope($user), $mutation);
        return $mutation && $team && $team['championship_status'] === 'archived' ? null : $team;
    }

    private function findBySlugForMutation(array $user, string $slug): ?array
    {
        $permission = $this->access->scope($user) === 'team' ? 'teams.manage_own' : 'teams.update';
        if ($this->authorization->cannot($user, $permission)) return null;
        $team = $this->teams->findForUserBySlug($slug, (int) $user['id'], $this->access->scope($user), true);
        return $team && $team['championship_status'] !== 'archived' ? $team : null;
    }

    private function canStaff(array $user, string $globalPermission, int $teamId): bool
    {
        $permission = $this->access->scope($user) === 'team' ? 'team_staff.manage_own' : $globalPermission;
        return $this->authorization->can($user, $permission) && $this->access->findForEitherMutation($user, $teamId, 'teams.update', 'teams.manage_own') !== null;
    }

    private function canFormation(array $user, int $teamId): bool
    {
        return $this->authorization->can($user, 'teams.select_default_formation') && $this->access->find($user, $teamId, 'teams.select_default_formation', true) !== null;
    }

    private function staffMutationContext(Request $request, string $value): array
    {
        [$guard, $team] = $this->mutationContext($request, $value);
        if ($guard instanceof Response) return [$guard, null];
        if (!$this->canStaff($guard, 'team_staff.create', (int) $team['id'])) return [Response::forbidden(), null];
        return [$guard, $team];
    }

    private function teamData(Request $request, string $status): array
    {
        return ['championship_id' => (int) ($request->body['championship_id'] ?? 0), 'name' => trim((string) ($request->body['name'] ?? '')), 'short_name' => trim((string) ($request->body['short_name'] ?? '')), 'slug' => Slugger::make((string) ($request->body['slug'] ?? $request->body['name'] ?? '')), 'abbreviation' => strtoupper(trim((string) ($request->body['abbreviation'] ?? ''))), 'description' => trim((string) ($request->body['description'] ?? '')), 'city' => trim((string) ($request->body['city'] ?? '')), 'state' => trim((string) ($request->body['state'] ?? '')), 'primary_color' => (string) ($request->body['primary_color'] ?? '#123C32'), 'secondary_color' => (string) ($request->body['secondary_color'] ?? '#D9A441'), 'status' => $status, 'default_tactical_formation_id' => (int) ($request->body['default_tactical_formation_id'] ?? 0), 'shield_path' => null];
    }

    private function staffData(Request $request, ?string $photoPath): array
    {
        return ['staff_role_id' => (int) ($request->body['staff_role_id'] ?? 0), 'user_id' => (int) ($request->body['user_id'] ?? 0), 'full_name' => trim((string) ($request->body['full_name'] ?? '')), 'display_name' => trim((string) ($request->body['display_name'] ?? '')), 'email' => trim((string) ($request->body['email'] ?? '')), 'phone' => trim((string) ($request->body['phone'] ?? '')), 'document_number' => '', 'photo_path' => $photoPath, 'registration_number' => trim((string) ($request->body['registration_number'] ?? '')), 'status' => (string) ($request->body['status'] ?? 'active'), 'starts_at' => $request->body['starts_at'] ?? '', 'ends_at' => $request->body['ends_at'] ?? '', 'notes' => trim((string) ($request->body['notes'] ?? ''))];
    }

    private function blank(): array
    {
        return ['championship_id' => '', 'name' => '', 'short_name' => '', 'slug' => '', 'abbreviation' => '', 'description' => '', 'city' => '', 'state' => '', 'primary_color' => '#123C32', 'secondary_color' => '#D9A441', 'status' => 'draft', 'default_tactical_formation_id' => ''];
    }

    private function blankStaff(): array
    {
        return ['staff_role_id' => '', 'user_id' => '', 'full_name' => '', 'display_name' => '', 'email' => '', 'phone' => '', 'document_number' => '', 'photo_path' => '', 'registration_number' => '', 'status' => 'active', 'starts_at' => '', 'ends_at' => '', 'notes' => ''];
    }

    private function teamError(string $title, array $record, array $user, array $errors, int $status, bool $editing = false): Response
    {
        return $this->errorPage($title, 'admin/teams/form', ['user' => $user, 'record' => $record, 'championships' => $editing ? [] : $this->access->authorizedChampionships($user), 'formations' => $this->formations->list(), 'errors' => $errors, 'editing' => $editing], $status);
    }

    private function assignmentError(array $user, array $team, array $errors): Response
    {
        return $this->errorPage('Responsaveis da equipe', 'admin/teams/assignments', ['user' => $user, 'team' => $team, 'assignments' => $this->teams->assignments((int) $team['id']), 'candidates' => $this->users->listByRole('team_manager'), 'canManage' => true, 'errors' => $errors], 422);
    }

    private function staffError(array $user, array $team, array $record, array $errors, bool $editing): Response
    {
        return $this->errorPage('Comissao tecnica', 'admin/teams/staff-form', ['user' => $user, 'team' => $team, 'record' => $record, 'roles' => $this->staffRoles->listActive(), 'users' => $this->users->listByRole('team_manager'), 'editing' => $editing, 'errors' => $errors], 422);
    }
}
