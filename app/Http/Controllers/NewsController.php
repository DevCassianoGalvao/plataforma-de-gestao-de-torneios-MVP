<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\NewsRepository;
use App\Services\NewsAccessService;
use App\Services\NewsService;
use App\Services\StorageService;

final class NewsController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly NewsRepository $news, private readonly NewsAccessService $access, private readonly NewsService $service, private readonly StorageService $storage)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function adminIndex(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'content.manage'); if ($guard instanceof Response) return $guard;
        $allowed = $this->access->allowedChampionshipIds($guard); $page = max(1, (int) ($request->query['page'] ?? 1)); $filters = ['q' => (string) ($request->query['q'] ?? ''), 'status' => (string) ($request->query['status'] ?? ''), 'championship_id' => (string) ($request->query['championship_id'] ?? '')];
        $total = $this->news->countAdmin($allowed, $filters); $items = $this->news->listAdmin($allowed, $filters, 20, ($page - 1) * 20);
        return $this->page('Noticias', 'admin/news/index', ['user' => $guard, 'items' => $items, 'championships' => $this->news->championshipsForUser((int) $guard['id'], $this->authorization->roleKeys($guard), $this->access->isAdministrator($guard)), 'filters' => $filters, 'page' => $page, 'pages' => max(1, (int) ceil($total / 20)), 'statuses' => NewsRepository::STATUSES, 'message' => Session::consumeFlash('news_message')]);
    }

    public function createForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'content.manage'); if ($guard instanceof Response) return $guard;
        return $this->page('Nova noticia', 'admin/news/form', ['user' => $guard, 'record' => ['status' => 'draft', 'featured' => 0], 'editing' => false, 'championships' => $this->championships($guard), 'errors' => []]);
    }

    public function create(Request $request, array $params = []): Response
    {
        return $this->save($request, null);
    }

    public function editForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'content.manage'); if ($guard instanceof Response) return $guard; $article = $this->articleForUser($guard, (int) ($params[0] ?? 0)); if (!$article) return Response::forbidden();
        return $this->page('Editar noticia', 'admin/news/form', ['user' => $guard, 'record' => $article, 'editing' => true, 'championships' => $this->championships($guard), 'errors' => []]);
    }

    public function update(Request $request, array $params = []): Response
    {
        return $this->save($request, (int) ($params[0] ?? 0));
    }

    public function adminShow(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'content.manage'); if ($guard instanceof Response) return $guard; $article = $this->articleForUser($guard, (int) ($params[0] ?? 0)); if (!$article) return Response::forbidden();
        return $this->page('Prévia da noticia', 'admin/news/show', ['user' => $guard, 'article' => $article, 'preview' => true, 'message' => Session::consumeFlash('news_message')]);
    }

    public function publish(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'content.publish'); if ($guard instanceof Response) return $guard; if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.'); $article = $this->articleForUser($guard, (int) ($params[0] ?? 0)); if (!$article || !$this->access->canPublishChampionship($guard, (int) $article['championship_id'])) return Response::forbidden();
        $this->service->publish((int) $article['id'], (int) $guard['id']); Session::flash('news_message', 'Noticia publicada.'); return $this->backToAdmin($article['id']);
    }

    public function unpublish(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'content.publish'); if ($guard instanceof Response) return $guard; if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.'); $article = $this->articleForUser($guard, (int) ($params[0] ?? 0)); if (!$article || !$this->access->canPublishChampionship($guard, (int) $article['championship_id'])) return Response::forbidden();
        $this->service->unpublish((int) $article['id'], (int) $guard['id']); Session::flash('news_message', 'Noticia retirada do portal.'); return $this->backToAdmin($article['id']);
    }

    public function delete(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'content.manage'); if ($guard instanceof Response) return $guard; if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.'); $article = $this->articleForUser($guard, (int) ($params[0] ?? 0)); if (!$article) return Response::forbidden();
        $this->service->delete((int) $article['id'], (int) $guard['id']); Session::flash('news_message', 'Noticia arquivada.'); return $this->redirect('/admin/noticias');
    }

    public function adminCover(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'content.manage'); if ($guard instanceof Response) return $guard; $article = $this->articleForUser($guard, (int) ($params[0] ?? 0)); if (!$article || !$article['cover_image_path']) return Response::html('Imagem nao encontrada.', 404); $file = $this->storage->read((string) $article['cover_image_path']); if (!$file) return Response::html('Imagem nao encontrada.', 404); return Response::binary($file['body'], $file['mime'], 'capa-noticia.jpg');
    }

    public function publicIndex(Request $request, array $params = []): Response
    {
        $championship = $this->news->publicChampionship((string) ($params[0] ?? '')); if (!$championship) return Response::html('Campeonato nao encontrado.', 404); $page = max(1, (int) ($request->query['page'] ?? 1)); $search = trim((string) ($request->query['q'] ?? '')); $total = $this->news->countPublished((int) $championship['id'], $search); $items = $this->news->listPublished((int) $championship['id'], $search, 12, ($page - 1) * 12);
        return $this->page($championship['name'] . ' - Noticias', 'public/news/index', ['championship' => $championship, 'items' => $items, 'search' => $search, 'page' => $page, 'pages' => max(1, (int) ceil($total / 12))]);
    }

    public function publicRecent(Request $request, array $params = []): Response
    {
        return $this->publicIndex($request, $params);
    }

    public function publicShow(Request $request, array $params = []): Response
    {
        $article = $this->news->publicFind((string) ($params[0] ?? ''), (string) ($params[1] ?? '')); if (!$article) return Response::html('Noticia nao encontrada.', 404); return $this->page($article['title'], 'public/news/show', ['article' => $article]);
    }

    public function publicCover(Request $request, array $params = []): Response
    {
        $article = $this->news->publicFind((string) ($params[0] ?? ''), (string) ($params[1] ?? '')); if (!$article || !$article['cover_image_path']) return Response::html('Imagem nao encontrada.', 404); $file = $this->storage->read((string) $article['cover_image_path']); if (!$file) return Response::html('Imagem nao encontrada.', 404); return new Response($file['body'], 200, ['Content-Type' => $file['mime'], 'Cache-Control' => 'public, max-age=3600']);
    }

    private function save(Request $request, ?int $id): Response
    {
        $guard = $this->guard($request, 'content.manage'); if ($guard instanceof Response) return $guard; if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.'); $existing = $id ? $this->articleForUser($guard, $id) : null; if ($id && !$existing) return Response::forbidden(); $championshipId = (int) ($request->body['championship_id'] ?? ($existing['championship_id'] ?? 0)); if (!$this->access->canManageChampionship($guard, $championshipId)) return Response::forbidden(); $result = $this->service->save($guard, $request->body, $request->files['cover_image'] ?? null, $id); if (!$result['ok']) return $this->errorPage($id ? 'Editar noticia' : 'Nova noticia', 'admin/news/form', ['user' => $guard, 'record' => $result['record'] ?? $request->body, 'editing' => $id !== null, 'championships' => $this->championships($guard), 'errors' => $result['errors']], 422); Session::flash('news_message', 'Noticia salva.'); return $this->redirect('/admin/noticias/' . $result['id']);
    }

    private function articleForUser(array $user, int $id): ?array
    {
        $article = $this->news->find($id); return $article && $this->access->canManageChampionship($user, (int) $article['championship_id']) ? $article : null;
    }

    private function championships(array $user): array { return $this->news->championshipsForUser((int) $user['id'], $this->authorization->roleKeys($user), $this->access->isAdministrator($user)); }
    private function redirect(string $path): Response { return Response::redirect(\App\Core\Config::url($path)); }
    private function backToAdmin(int $id): Response { return $this->redirect('/admin/noticias/' . $id); }
}
