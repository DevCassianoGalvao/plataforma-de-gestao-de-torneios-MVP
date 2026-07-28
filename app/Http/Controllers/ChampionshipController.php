<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CategoryRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\SeasonRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\ChampionshipAccessService;
use App\Services\ChampionshipStatusService;
use App\Services\ColorRules;
use App\Services\DateRules;
use App\Services\RegulationService;
use App\Services\StorageService;
use App\Services\Slugger;

final class ChampionshipController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, AuditService $audit, private readonly ChampionshipRepository $championships, private readonly SeasonRepository $seasons, private readonly CategoryRepository $categories, private readonly ChampionshipAccessService $access, private readonly ChampionshipStatusService $statusService, private readonly RegulationService $regulations, private readonly StorageService $storage, private readonly UserRepository $userRepository)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Campeonatos', 'admin/championships/index', ['user' => $guard, 'items' => $this->championships->listForUser((int) $guard['id'], $this->access->isAdministrator($guard), ['search' => $request->query['q'] ?? '', 'status' => $request->query['status'] ?? '', 'season_id' => $request->query['season_id'] ?? '', 'category_id' => $request->query['category_id'] ?? '']), 'seasons' => $this->seasons->list(true), 'categories' => $this->categories->list(true), 'query' => $request->query, 'message' => Session::consumeFlash('championship_message')]);
    }

    public function createForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.create');
        if ($guard instanceof Response) return $guard;
        return $this->page('Novo campeonato', 'admin/championships/form', ['user' => $guard, 'record' => $this->blank(), 'seasons' => $this->seasons->list(), 'categories' => $this->categories->list(), 'errors' => []]);
    }

    public function create(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.create');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return $this->errorPage('Novo campeonato', 'admin/championships/form', ['record' => $request->body, 'seasons' => $this->seasons->list(), 'categories' => $this->categories->list(), 'errors' => ['A sessao expirou.']], 419);
        $data = $this->generalData($request);
        $errors = $this->validateGeneral($data);
        if ($errors) return $this->errorPage('Novo campeonato', 'admin/championships/form', ['record' => $data, 'seasons' => $this->seasons->list(), 'categories' => $this->categories->list(), 'errors' => $errors], 422);
        try {
            $id = $this->championships->create($data, (int) $guard['id']);
            $this->regulations->createInitialDraft($id, (int) $guard['id'], $request);
            $this->audit->record('championships.created', (int) $guard['id'], 'championship', $id, [], $request);
            if (!$this->access->isAdministrator($guard)) {
                $this->championships->assign($id, (int) $guard['id'], 'organizer', (int) $guard['id']);
                $this->audit->record('championships.organizer_assigned', (int) $guard['id'], 'championship', $id, ['user_id' => (int) $guard['id']], $request);
            }
        } catch (\PDOException) {
            return $this->errorPage('Novo campeonato', 'admin/championships/form', ['record' => $data, 'seasons' => $this->seasons->list(), 'categories' => $this->categories->list(), 'errors' => ['Ja existe um campeonato com esse slug.']], 422);
        }
        Session::flash('championship_message', 'Campeonato criado como rascunho.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $data['slug']));
    }

    public function show(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.view');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard);
        if (!$championship) return $this->access->isAdministrator($guard) ? Response::html('Campeonato nao encontrado.', 404) : Response::forbidden();
        return $this->page((string) $championship['name'], 'admin/championships/show', ['user' => $guard, 'championship' => $championship, 'assignments' => $this->championships->assignments((int) $championship['id']), 'regulation' => $this->regulationsFor($championship), 'message' => Session::consumeFlash('championship_message')]);
    }

    public function editForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.update');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        return $this->page('Editar campeonato', 'admin/championships/form', ['user' => $guard, 'record' => $championship, 'seasons' => $this->seasons->list(), 'categories' => $this->categories->list(), 'errors' => [], 'editing' => true]);
    }

    public function update(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.update');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        if (!$this->validCsrf($request)) return $this->errorPage('Editar campeonato', 'admin/championships/form', ['record' => $championship, 'seasons' => $this->seasons->list(), 'categories' => $this->categories->list(), 'errors' => ['A sessao expirou.'], 'editing' => true], 419);
        $data = $this->generalData($request);
        $errors = $this->validateGeneral($data);
        if ($errors) return $this->errorPage('Editar campeonato', 'admin/championships/form', ['record' => $data, 'seasons' => $this->seasons->list(), 'categories' => $this->categories->list(), 'errors' => $errors, 'editing' => true], 422);
        try {
            $this->championships->updateGeneral((int) $championship['id'], $data);
            $this->audit->record('championships.updated', (int) $guard['id'], 'championship', (int) $championship['id'], [], $request);
        } catch (\PDOException) {
            return $this->errorPage('Editar campeonato', 'admin/championships/form', ['record' => $data, 'seasons' => $this->seasons->list(), 'categories' => $this->categories->list(), 'errors' => ['Ja existe um campeonato com esse slug.'], 'editing' => true], 422);
        }
        Session::flash('championship_message', 'Informacoes gerais atualizadas.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $data['slug']));
    }

    public function identity(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_identity');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $data = ['default_theme' => (string) ($request->body['default_theme'] ?? 'light'), 'primary_color' => (string) ($request->body['primary_color'] ?? ''), 'secondary_color' => (string) ($request->body['secondary_color'] ?? ''), 'accent_color' => (string) ($request->body['accent_color'] ?? ''), 'logo_path' => $championship['logo_path'], 'logo_light_path' => $championship['logo_light_path'], 'logo_dark_path' => $championship['logo_dark_path'], 'banner_path' => $championship['banner_path'], 'favicon_path' => $championship['favicon_path'], 'social_image_path' => $championship['social_image_path']];
        $errors = [];
        if (!in_array($data['default_theme'], ['light', 'dark'], true)) $errors[] = 'Tema invalido.';
        foreach (['primary_color', 'secondary_color', 'accent_color'] as $color) if (!ColorRules::valid($data[$color])) $errors[] = 'Use cores no formato #RRGGBB.';
        $fields = ['logo_path', 'logo_light_path', 'logo_dark_path', 'banner_path', 'favicon_path', 'social_image_path'];
        foreach ($fields as $field) {
            if (!isset($request->files[$field]) || ($request->files[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            try {
                $allowed = $field === 'favicon_path' ? ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'] : ['image/png', 'image/jpeg', 'image/webp'];
                $stored = $this->storage->store($request->files[$field], 'championships/' . $championship['id'], $allowed, 5242880);
                $data[$field] = $stored['path'];
            } catch (\Throwable $exception) {
                $errors[] = $field . ': ' . $exception->getMessage();
            }
        }
        if ($errors) return $this->errorPage('Identidade', 'admin/championships/identity', ['user' => $guard, 'championship' => array_merge($championship, $data), 'errors' => $errors], 422);
        $this->championships->updateIdentity((int) $championship['id'], $data);
        $this->audit->record('championships.identity_updated', (int) $guard['id'], 'championship', (int) $championship['id'], ['uploads' => array_values(array_filter(array_map(static fn ($field): ?string => $data[$field] !== $championship[$field] ? $field : null, $fields)))], $request);
        Session::flash('championship_message', 'Identidade atualizada.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug']));
    }

    public function identityForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_identity');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        return $this->page('Identidade do campeonato', 'admin/championships/identity', ['user' => $guard, 'championship' => $championship, 'errors' => []]);
    }

    public function status(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.update');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->statusService->transition($championship, (string) ($request->body['status'] ?? ''));
        if (!$result['ok']) return $this->errorPage('Status do campeonato', 'errors/simple', ['message' => $result['message']], 422);
        $this->audit->record('championships.status_changed', (int) $guard['id'], 'championship', (int) $championship['id'], ['status' => $request->body['status']], $request);
        Session::flash('championship_message', 'Status atualizado.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug']));
    }

    public function archive(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.archive');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $result = $this->statusService->transition($championship, 'archived');
        if (!$result['ok']) return $this->errorPage('Arquivar campeonato', 'errors/simple', ['message' => $result['message']], 422);
        $this->audit->record('championships.archived', (int) $guard['id'], 'championship', (int) $championship['id'], [], $request);
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug']));
    }

    public function assignments(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_assignments');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship || !$this->access->canManageAssignments($guard, (int) $championship['id'])) return Response::forbidden();
        return $this->page('Organizadores', 'admin/championships/assignments', ['user' => $guard, 'championship' => $championship, 'assignments' => $this->championships->assignments((int) $championship['id']), 'candidates' => $this->userRepository->listByRole('organizer'), 'errors' => []]);
    }

    public function assign(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_assignments');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship || !$this->access->canManageAssignments($guard, (int) $championship['id'])) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $candidate = $this->userRepository->findById((int) ($request->body['user_id'] ?? 0));
        if (!$candidate || !in_array('organizer', $this->authorization->roleKeys($candidate), true)) return $this->errorPage('Organizadores', 'admin/championships/assignments', ['championship' => $championship, 'assignments' => $this->championships->assignments((int) $championship['id']), 'candidates' => $this->userRepository->listByRole('organizer'), 'errors' => ['Escolha um organizador valido.']], 422);
        try {
            $this->championships->assign((int) $championship['id'], (int) $candidate['id'], 'organizer', (int) $guard['id']);
        } catch (\PDOException) {
            return $this->errorPage('Organizadores', 'admin/championships/assignments', ['championship' => $championship, 'assignments' => $this->championships->assignments((int) $championship['id']), 'candidates' => $this->userRepository->listByRole('organizer'), 'errors' => ['Este organizador ja esta vinculado.']], 422);
        }
        $this->audit->record('championships.organizer_assigned', (int) $guard['id'], 'championship', (int) $championship['id'], ['user_id' => (int) $candidate['id']], $request);
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/organizadores'));
    }

    public function unassign(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_assignments');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship || !$this->access->canManageAssignments($guard, (int) $championship['id'])) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $userId = (int) ($params[1] ?? 0);
        $this->championships->unassign((int) $championship['id'], $userId, 'organizer');
        $this->audit->record('championships.organizer_unassigned', (int) $guard['id'], 'championship', (int) $championship['id'], ['user_id' => $userId], $request);
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/organizadores'));
    }

    public function asset(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.view');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard);
        if (!$championship) return Response::html('Arquivo nao encontrado.', 404);
        $field = (string) ($params[1] ?? '');
        if (!in_array($field, ['logo_path', 'logo_light_path', 'logo_dark_path', 'banner_path', 'favicon_path', 'social_image_path'], true)) return Response::html('Arquivo nao encontrado.', 404);
        $file = $this->storage->read((string) ($championship[$field] ?? ''));
        if (!$file) return Response::html('Arquivo nao encontrado.', 404);
        return Response::binary($file['body'], $file['mime'], $file['name']);
    }

    private function resolve(string $value, array $user, bool $mutation = false): ?array
    {
        $administrator = $this->access->isAdministrator($user);
        $championship = ctype_digit($value) ? $this->championships->findForUser((int) $value, (int) $user['id'], $administrator) : $this->championships->findForUserBySlug($value, (int) $user['id'], $administrator);
        if (!$championship) return null;
        if ($mutation && $championship['status'] === 'archived') return null;
        return $championship;
    }

    private function regulationsFor(array $championship): ?array
    {
        return $this->regulationsRepository()->published((int) $championship['id']) ?: $this->regulationsRepository()->draft((int) $championship['id']);
    }

    private function regulationsRepository(): \App\Repositories\RegulationRepository
    {
        return new \App\Repositories\RegulationRepository(\App\Core\Database::connection());
    }

    private function blank(): array
    {
        return ['name' => '', 'short_name' => '', 'slug' => '', 'description' => '', 'season_id' => '', 'category_id' => '', 'starts_at' => '', 'ends_at' => '', 'registration_starts_at' => '', 'registration_ends_at' => '', 'visibility' => 'private', 'default_theme' => 'light', 'primary_color' => '#123C32', 'secondary_color' => '#245C4A', 'accent_color' => '#D9A441'];
    }

    private function generalData(Request $request): array
    {
        return ['name' => trim((string) ($request->body['name'] ?? '')), 'short_name' => trim((string) ($request->body['short_name'] ?? '')), 'slug' => Slugger::make((string) ($request->body['slug'] ?? $request->body['name'] ?? '')), 'description' => trim((string) ($request->body['description'] ?? '')), 'season_id' => (int) ($request->body['season_id'] ?? 0), 'category_id' => (int) ($request->body['category_id'] ?? 0), 'starts_at' => $request->body['starts_at'] ?? null, 'ends_at' => $request->body['ends_at'] ?? null, 'registration_starts_at' => $request->body['registration_starts_at'] ?? null, 'registration_ends_at' => $request->body['registration_ends_at'] ?? null, 'status' => 'draft', 'visibility' => (string) ($request->body['visibility'] ?? 'private'), 'default_theme' => (string) ($request->body['default_theme'] ?? 'light'), 'primary_color' => (string) ($request->body['primary_color'] ?? '#123C32'), 'secondary_color' => (string) ($request->body['secondary_color'] ?? '#245C4A'), 'accent_color' => (string) ($request->body['accent_color'] ?? '#D9A441')];
    }

    private function validateGeneral(array $data): array
    {
        $errors = [];
        if (strlen($data['name']) < 2 || strlen($data['short_name']) < 2 || $data['slug'] === '') $errors[] = 'Nome, nome curto e slug sao obrigatorios.';
        if ($data['season_id'] < 1 || !$this->seasons->find($data['season_id'])) $errors[] = 'Escolha uma temporada valida.';
        if ($data['category_id'] < 1 || !$this->categories->find($data['category_id'])) $errors[] = 'Escolha uma categoria valida.';
        if (!in_array($data['visibility'], ['private', 'public'], true)) $errors[] = 'Visibilidade invalida.';
        if (!in_array($data['default_theme'], ['light', 'dark'], true)) $errors[] = 'Tema invalido.';
        foreach (['primary_color', 'secondary_color', 'accent_color'] as $color) if (!ColorRules::valid($data[$color])) $errors[] = 'Use cores no formato #RRGGBB.';
        return array_merge($errors, DateRules::validate(['starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'registration_starts_at' => $data['registration_starts_at'], 'registration_ends_at' => $data['registration_ends_at']]));
    }
}
