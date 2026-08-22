<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CategoryRepository;
use App\Repositories\ChampionshipRepository;
use App\Repositories\ChampionshipCarouselRepository;
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
    public function __construct($users, \App\Services\AuthorizationService $authorization, AuditService $audit, private readonly ChampionshipRepository $championships, private readonly ChampionshipCarouselRepository $carousel, private readonly SeasonRepository $seasons, private readonly CategoryRepository $categories, private readonly ChampionshipAccessService $access, private readonly ChampionshipStatusService $statusService, private readonly RegulationService $regulations, private readonly StorageService $storage, private readonly UserRepository $userRepository)
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
        return $this->page((string) $championship['name'], 'admin/championships/show', ['user' => $guard, 'championship' => $championship, 'regulation' => $this->regulationsFor($championship), 'message' => Session::consumeFlash('championship_message')]);
    }

    public function accountability(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_assignments');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        return $this->page('Prestação de contas do campeonato', 'admin/championships/accountability', $this->accountabilityViewData($guard, $championship));
    }

    public function assignAccountability(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_assignments');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $userId = (int) ($request->body['user_id'] ?? 0);
        $candidate = $this->userRepository->findById($userId);
        $roleKeys = $candidate ? array_map(static fn (array $role): string => (string) $role['key'], $this->userRepository->roles($userId)) : [];
        if (!$candidate || $candidate['status'] !== 'active' || !in_array('accountability', $roleKeys, true)) {
            return $this->errorPage('Prestação de contas', 'admin/championships/accountability', $this->accountabilityViewData($guard, $championship, ['Selecione um usuário ativo com o perfil Prestação de Contas.']), 422);
        }
        $this->championships->assignAccountability((int) $championship['id'], $userId, (int) $guard['id']);
        $this->audit->record('championships.accountability_assigned', (int) $guard['id'], 'championship', (int) $championship['id'], ['user_id' => $userId], $request);
        Session::flash('championship_accountability_message', 'Usuário vinculado à prestação de contas.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/prestacao'));
    }

    public function unassignAccountability(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_assignments');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato nao encontrado ou sem acesso.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $userId = (int) ($params[1] ?? 0);
        $this->championships->unassignAccountability((int) $championship['id'], $userId);
        $this->audit->record('championships.accountability_unassigned', (int) $guard['id'], 'championship', (int) $championship['id'], ['user_id' => $userId], $request);
        Session::flash('championship_accountability_message', 'Vínculo de prestação de contas encerrado.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/prestacao'));
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
        $data = ['default_theme' => 'dark', 'primary_color' => (string) ($request->body['primary_color'] ?? ''), 'secondary_color' => (string) ($request->body['secondary_color'] ?? ''), 'accent_color' => (string) ($request->body['accent_color'] ?? ''), 'logo_path' => $championship['logo_path'], 'logo_light_path' => $championship['logo_light_path'], 'logo_dark_path' => $championship['logo_dark_path'], 'banner_path' => $championship['banner_path'], 'favicon_path' => $championship['favicon_path'], 'social_image_path' => $championship['social_image_path']];
        $errors = [];
        $uploadedAssets = [];
        foreach (['primary_color', 'secondary_color', 'accent_color'] as $color) if (!ColorRules::valid($data[$color])) $errors[] = 'Use cores no formato #RRGGBB.';
        $fields = ['logo_path', 'logo_light_path', 'logo_dark_path', 'banner_path', 'favicon_path', 'social_image_path'];
        foreach ($fields as $field) {
            if (!isset($request->files[$field]) || ($request->files[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            try {
                if ($field === 'favicon_path') {
                    $stored = $this->storage->store($request->files[$field], 'championships/' . $championship['id'], ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'], 5242880);
                } else {
                    $size = match ($field) {
                        'banner_path' => ['max_width' => 1920, 'max_height' => 1080],
                        'social_image_path' => ['max_width' => 1200, 'max_height' => 630],
                        default => ['max_width' => 1400, 'max_height' => 1400, 'quality' => 86],
                    };
                    $stored = $this->storage->storeOptimizedImage($request->files[$field], 'championships/' . $championship['id'], $size);
                }
                $data[$field] = $stored['path'];
                $uploadedAssets[$field] = $stored['path'];
            } catch (\Throwable $exception) {
                $errors[] = $field . ': ' . $exception->getMessage();
            }
        }
        if ($errors) {
            foreach ($uploadedAssets as $path) $this->storage->delete($path);
            return $this->errorPage('Identidade', 'admin/championships/identity', ['user' => $guard, 'championship' => array_merge($championship, $data), 'errors' => $errors], 422);
        }
        $this->championships->updateIdentity((int) $championship['id'], $data);
        foreach ($uploadedAssets as $field => $path) if (!empty($championship[$field]) && $championship[$field] !== $path) $this->storage->delete((string) $championship[$field]);
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

    public function carouselForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_identity');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato não encontrado ou sem acesso.', 404);
        return $this->page('Carrossel do campeonato', 'admin/championships/carousel', ['user' => $guard, 'championship' => $championship, 'carouselSlides' => $this->carousel->listForChampionship((int) $championship['id'])]);
    }

    public function createCarouselSlide(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_identity');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato não encontrado ou sem acesso.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessão expirou.');
        $title = trim((string) ($request->body['title'] ?? ''));
        $link = trim((string) ($request->body['link_url'] ?? ''));
        if ($title === '' || mb_strlen($title) > 180) return Response::html('Informe um título de até 180 caracteres.', 422);
        if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL) && !str_starts_with($link, '/')) return Response::html('Informe um link válido ou um caminho interno iniciado por /.', 422);
        try {
            $file = $request->files['image'] ?? [];
            $stored = $this->storage->storeOptimizedImage($file, 'championships/' . $championship['id'] . '/carousel', ['max_width' => 1920, 'max_height' => 1080, 'quality' => 84]);
            $id = $this->carousel->create((int) $championship['id'], $title, $link !== '' ? $link : null, $stored['path'], (int) ($request->body['display_order'] ?? 0));
            $this->audit->record('championships.carousel_slide_created', (int) $guard['id'], 'championship_carousel_slide', $id, [], $request);
            Session::flash('championship_message', 'Slide do carrossel adicionado.');
        } catch (\Throwable $exception) {
            return Response::html($exception->getMessage(), 422);
        }
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade/carrossel'));
    }

    public function deleteCarouselSlide(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'championships.manage_identity');
        if ($guard instanceof Response) return $guard;
        $championship = $this->resolve($params[0] ?? '', $guard, true);
        if (!$championship) return Response::html('Campeonato não encontrado ou sem acesso.', 404);
        if (!$this->validCsrf($request)) return Response::forbidden('A sessão expirou.');
        $slide = $this->carousel->findForChampionship((int) ($params[1] ?? 0), (int) $championship['id']);
        if (!$slide) return Response::html('Slide não encontrado.', 404);
        $this->carousel->delete((int) $slide['id'], (int) $championship['id']);
        $this->storage->delete((string) $slide['image_path']);
        $this->audit->record('championships.carousel_slide_deleted', (int) $guard['id'], 'championship_carousel_slide', (int) $slide['id'], [], $request);
        Session::flash('championship_message', 'Slide removido.');
        return Response::redirect(Config::url('/admin/campeonatos/' . $championship['slug'] . '/identidade/carrossel'));
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

    private function accountabilityViewData(array $user, array $championship, array $errors = []): array
    {
        return [
            'user' => $user,
            'championship' => $championship,
            'assignments' => $this->championships->accountabilityAssignments((int) $championship['id']),
            'candidates' => $this->userRepository->listByRole('accountability'),
            'errors' => $errors,
            'message' => Session::consumeFlash('championship_accountability_message'),
        ];
    }

    private function blank(): array
    {
        return ['name' => '', 'short_name' => '', 'slug' => '', 'description' => '', 'season_id' => '', 'category_id' => '', 'requires_guardian' => 0, 'allow_underage_athletes' => 0, 'starts_at' => '', 'ends_at' => '', 'registration_starts_at' => '', 'registration_ends_at' => '', 'visibility' => 'private', 'default_theme' => 'dark', 'primary_color' => '#123C32', 'secondary_color' => '#245C4A', 'accent_color' => '#D9A441'];
    }

    private function generalData(Request $request): array
    {
        $allowUnderage = !empty($request->body['allow_underage_athletes']) ? 1 : 0;
        $requiresGuardian = (!empty($request->body['requires_guardian']) || $allowUnderage === 1) ? 1 : 0;
        return ['name' => trim((string) ($request->body['name'] ?? '')), 'short_name' => trim((string) ($request->body['short_name'] ?? '')), 'slug' => Slugger::make((string) ($request->body['slug'] ?? $request->body['name'] ?? '')), 'description' => trim((string) ($request->body['description'] ?? '')), 'season_id' => (int) ($request->body['season_id'] ?? 0), 'category_id' => (int) ($request->body['category_id'] ?? 0), 'requires_guardian' => $requiresGuardian, 'allow_underage_athletes' => $allowUnderage, 'starts_at' => $request->body['starts_at'] ?? null, 'ends_at' => $request->body['ends_at'] ?? null, 'registration_starts_at' => $request->body['registration_starts_at'] ?? null, 'registration_ends_at' => $request->body['registration_ends_at'] ?? null, 'status' => 'draft', 'visibility' => (string) ($request->body['visibility'] ?? 'private'), 'default_theme' => 'dark', 'primary_color' => (string) ($request->body['primary_color'] ?? '#123C32'), 'secondary_color' => (string) ($request->body['secondary_color'] ?? '#245C4A'), 'accent_color' => (string) ($request->body['accent_color'] ?? '#D9A441')];
    }

    private function validateGeneral(array $data): array
    {
        $errors = [];
        if (strlen($data['name']) < 2 || strlen($data['short_name']) < 2 || $data['slug'] === '') $errors[] = 'Nome, nome curto e slug sao obrigatorios.';
        if ($data['season_id'] < 1 || !$this->seasons->find($data['season_id'])) $errors[] = 'Escolha uma temporada valida.';
        if ($data['category_id'] < 1 || !$this->categories->find($data['category_id'])) $errors[] = 'Escolha uma categoria valida.';
        if (!in_array($data['visibility'], ['private', 'public'], true)) $errors[] = 'Visibilidade invalida.';
        foreach (['primary_color', 'secondary_color', 'accent_color'] as $color) if (!ColorRules::valid($data[$color])) $errors[] = 'Use cores no formato #RRGGBB.';
        return array_merge($errors, DateRules::validate(['starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'registration_starts_at' => $data['registration_starts_at'], 'registration_ends_at' => $data['registration_ends_at']]));
    }
}
