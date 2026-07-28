<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CategoryRepository;
use App\Repositories\SeasonRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\DateRules;
use App\Services\Slugger;

final class CatalogController extends Controller
{
    public function __construct($users, AuthorizationService $authorization, AuditService $audit, private readonly SeasonRepository $seasons, private readonly CategoryRepository $categories)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function seasons(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'seasons.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Temporadas', 'admin/catalog/seasons', ['user' => $guard, 'items' => $this->seasons->list(true), 'message' => Session::consumeFlash('catalog_message')]);
    }

    public function seasonForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'seasons.manage');
        if ($guard instanceof Response) return $guard;
        $record = isset($params[0]) ? $this->seasons->find((int) $params[0]) : ['name' => '', 'year' => date('Y'), 'starts_at' => '', 'ends_at' => '', 'status' => 'draft'];
        if (!$record) return Response::html('Temporada nao encontrada.', 404);
        return $this->page('Temporada', 'admin/catalog/season-form', ['user' => $guard, 'record' => $record, 'editing' => isset($params[0]), 'errors' => []]);
    }

    public function saveSeason(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'seasons.manage');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return $this->errorPage('Temporada', 'admin/catalog/season-form', ['record' => $request->body, 'editing' => isset($params[0]), 'errors' => ['A sessao expirou.']], 419);
        $data = ['name' => trim((string) ($request->body['name'] ?? '')), 'year' => (int) ($request->body['year'] ?? 0), 'starts_at' => $request->body['starts_at'] ?? null, 'ends_at' => $request->body['ends_at'] ?? null, 'status' => (string) ($request->body['status'] ?? 'draft')];
        $errors = [];
        if (strlen($data['name']) < 2 || $data['year'] < 2000 || $data['year'] > 2200) $errors[] = 'Nome e ano da temporada sao obrigatorios.';
        $errors = array_merge($errors, DateRules::validate(['starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at']]));
        if (!in_array($data['status'], ['draft', 'active', 'finished', 'archived'], true)) $errors[] = 'Status de temporada invalido.';
        if ($errors) return $this->errorPage('Temporada', 'admin/catalog/season-form', ['record' => $data, 'editing' => isset($params[0]), 'errors' => $errors], 422);
        try {
            $id = isset($params[0]) ? (int) $params[0] : $this->seasons->create($data);
            if (isset($params[0])) $this->seasons->update($id, $data);
            $this->audit->record(isset($params[0]) ? 'seasons.updated' : 'seasons.created', (int) $guard['id'], 'season', $id, [], $request);
        } catch (\PDOException) {
            return $this->errorPage('Temporada', 'admin/catalog/season-form', ['record' => $data, 'editing' => isset($params[0]), 'errors' => ['Ja existe uma temporada com esse nome e ano.']], 422);
        }
        Session::flash('catalog_message', 'Temporada salva.');
        return Response::redirect(Config::url('/admin/temporadas'));
    }

    public function categories(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'categories.view');
        if ($guard instanceof Response) return $guard;
        return $this->page('Categorias', 'admin/catalog/categories', ['user' => $guard, 'items' => $this->categories->list(true), 'message' => Session::consumeFlash('catalog_message')]);
    }

    public function categoryForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'categories.manage');
        if ($guard instanceof Response) return $guard;
        $record = isset($params[0]) ? $this->categories->find((int) $params[0]) : ['name' => '', 'slug' => '', 'description' => '', 'minimum_age' => '', 'maximum_age' => '', 'gender_rule' => '', 'status' => 'active'];
        if (!$record) return Response::html('Categoria nao encontrada.', 404);
        return $this->page('Categoria', 'admin/catalog/category-form', ['user' => $guard, 'record' => $record, 'editing' => isset($params[0]), 'errors' => []]);
    }

    public function saveCategory(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'categories.manage');
        if ($guard instanceof Response) return $guard;
        if (!$this->validCsrf($request)) return $this->errorPage('Categoria', 'admin/catalog/category-form', ['record' => $request->body, 'editing' => isset($params[0]), 'errors' => ['A sessao expirou.']], 419);
        $data = ['name' => trim((string) ($request->body['name'] ?? '')), 'slug' => Slugger::make((string) ($request->body['slug'] ?? $request->body['name'] ?? '')), 'description' => trim((string) ($request->body['description'] ?? '')), 'minimum_age' => ($request->body['minimum_age'] ?? '') === '' ? null : (int) $request->body['minimum_age'], 'maximum_age' => ($request->body['maximum_age'] ?? '') === '' ? null : (int) $request->body['maximum_age'], 'gender_rule' => trim((string) ($request->body['gender_rule'] ?? '')), 'status' => (string) ($request->body['status'] ?? 'active')];
        $errors = [];
        if (strlen($data['name']) < 2 || $data['slug'] === '') $errors[] = 'Nome da categoria e obrigatorio.';
        if ($data['minimum_age'] !== null && $data['minimum_age'] < 0) $errors[] = 'Idade minima invalida.';
        if ($data['maximum_age'] !== null && $data['maximum_age'] < 0) $errors[] = 'Idade maxima invalida.';
        if ($data['minimum_age'] !== null && $data['maximum_age'] !== null && $data['maximum_age'] < $data['minimum_age']) $errors[] = 'Idade maxima nao pode ser menor que minima.';
        if (!in_array($data['status'], ['active', 'inactive'], true)) $errors[] = 'Status de categoria invalido.';
        if ($errors) return $this->errorPage('Categoria', 'admin/catalog/category-form', ['record' => $data, 'editing' => isset($params[0]), 'errors' => $errors], 422);
        try {
            $id = isset($params[0]) ? (int) $params[0] : $this->categories->create($data);
            if (isset($params[0])) $this->categories->update($id, $data);
            $this->audit->record(isset($params[0]) ? 'categories.updated' : 'categories.created', (int) $guard['id'], 'category', $id, [], $request);
        } catch (\PDOException) {
            return $this->errorPage('Categoria', 'admin/catalog/category-form', ['record' => $data, 'editing' => isset($params[0]), 'errors' => ['Ja existe uma categoria com esse slug.']], 422);
        }
        Session::flash('catalog_message', 'Categoria salva.');
        return Response::redirect(Config::url('/admin/categorias'));
    }
}
