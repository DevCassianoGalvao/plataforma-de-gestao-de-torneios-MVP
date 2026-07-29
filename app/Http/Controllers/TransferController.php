<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\TransferRepository;
use App\Services\StorageService;
use App\Services\TransferAccessService;
use App\Services\TransferService;

final class TransferController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly TransferRepository $transfers, private readonly TransferAccessService $access, private readonly TransferService $service, private readonly StorageService $storage)
    { parent::__construct($users, $authorization, $audit); }

    public function adminIndex(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'transfers.manage'); if ($guard instanceof Response) return $guard; $allowed = $this->access->allowedChampionshipIds($guard); $filters = ['q' => (string) ($request->query['q'] ?? ''), 'type' => (string) ($request->query['type'] ?? ''), 'status' => (string) ($request->query['status'] ?? ''), 'championship_id' => (string) ($request->query['championship_id'] ?? '')]; $page = max(1, (int) ($request->query['page'] ?? 1)); $total = $this->transfers->countAdmin($allowed, $filters);
        return $this->page('Vai e Vem', 'admin/transfers/index', ['user' => $guard, 'items' => $this->transfers->listAdmin($allowed, $filters, 20, ($page - 1) * 20), 'championships' => $this->championships($guard), 'filters' => $filters, 'types' => TransferRepository::TYPES, 'statuses' => TransferRepository::STATUSES, 'page' => $page, 'pages' => max(1, (int) ceil($total / 20)), 'message' => Session::consumeFlash('transfer_message')]);
    }

    public function createForm(Request $request, array $params = []): Response { $guard = $this->guard($request, 'transfers.manage'); if ($guard instanceof Response) return $guard; return $this->formPage($guard, ['status' => 'draft', 'movement_date' => date('Y-m-d')], false, []); }
    public function editForm(Request $request, array $params = []): Response { $guard = $this->guard($request, 'transfers.manage'); if ($guard instanceof Response) return $guard; $item = $this->itemForUser($guard, (int) ($params[0] ?? 0)); if (!$item || !in_array($item['status'], ['draft', 'pending'], true)) return Response::forbidden(); return $this->formPage($guard, $item, true, []); }
    public function create(Request $request, array $params = []): Response { return $this->save($request, null); }
    public function update(Request $request, array $params = []): Response { return $this->save($request, (int) ($params[0] ?? 0)); }

    public function show(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'transfers.manage'); if ($guard instanceof Response) return $guard; $item = $this->itemForUser($guard, (int) ($params[0] ?? 0)); if (!$item) return Response::forbidden(); return $this->page('Movimentacao', 'admin/transfers/show', ['user' => $guard, 'item' => $item, 'history' => $this->transfers->history((int) $item['id']), 'message' => Session::consumeFlash('transfer_message')]);
    }
    public function approve(Request $request, array $params = []): Response { return $this->transition($request, $params, 'approved', 'approved', 'Aprovacao'); }
    public function publish(Request $request, array $params = []): Response { return $this->transition($request, $params, 'published', 'published', 'Publicacao'); }
    public function cancel(Request $request, array $params = []): Response { return $this->transition($request, $params, 'cancelled', 'cancelled', 'Cancelamento'); }

    public function publicIndex(Request $request, array $params = []): Response
    {
        $championship = $this->transfers->publicChampionship((string) ($params[0] ?? '')); if (!$championship) return Response::html('Campeonato nao encontrado.', 404); $filters = ['q' => trim((string) ($request->query['q'] ?? '')), 'type' => (string) ($request->query['type'] ?? '')]; $page = max(1, (int) ($request->query['page'] ?? 1)); $total = $this->transfers->countPublic((int) $championship['id'], $filters); return $this->publicPage($request, 'Vai e Vem', $championship, 'public/transfers/index', ['championship' => $championship, 'items' => $this->transfers->listPublic((int) $championship['id'], $filters, 12, ($page - 1) * 12), 'filters' => $filters, 'types' => TransferRepository::TYPES, 'page' => $page, 'pages' => max(1, (int) ceil($total / 12))]);
    }
    public function publicPhoto(Request $request, array $params = []): Response
    {
        $championship = $this->transfers->publicChampionship((string) ($params[0] ?? '')); if (!$championship) return Response::html('Imagem nao encontrada.', 404); $item = $this->transfers->publicFind((int) ($params[1] ?? 0), (int) $championship['id']); if (!$item || !$item['photo_path']) return Response::html('Imagem nao encontrada.', 404); $file = $this->storage->read((string) $item['photo_path']); if (!$file) return Response::html('Imagem nao encontrada.', 404); return new Response($file['body'], 200, ['Content-Type' => $file['mime'], 'Cache-Control' => 'public, max-age=3600']);
    }

    private function save(Request $request, ?int $id): Response
    {
        $guard = $this->guard($request, 'transfers.manage'); if ($guard instanceof Response) return $guard; if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.'); $existing = $id ? $this->itemForUser($guard, $id) : null; if ($id && !$existing) return Response::forbidden(); $championshipId = (int) ($request->body['championship_id'] ?? ($existing['championship_id'] ?? 0)); if (!$this->access->canManageChampionship($guard, $championshipId)) return Response::forbidden(); $result = $this->service->save($guard, $request->body, $id); if (!$result['ok']) return $this->errorPage('Vai e Vem', 'admin/transfers/form', array_merge($this->formData($guard, $result['record'] ?? $request->body, $id !== null), ['errors' => $result['errors']]), 422); Session::flash('transfer_message', 'Movimentacao salva.'); return $this->redirect('/admin/vai-e-vem/' . $result['id']);
    }
    private function transition(Request $request, array $params, string $to, string $action, string $message): Response
    {
        $guard = $this->guard($request, 'transfers.manage'); if ($guard instanceof Response) return $guard; if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.'); $id = (int) ($params[0] ?? 0); $item = $this->itemForUser($guard, $id); if (!$item) return Response::forbidden(); if (!$this->service->transition($id, (int) $guard['id'], $to, $action, trim((string) ($request->body['reason'] ?? '')) ?: null)) return Response::html('Transicao invalida.', 422); Session::flash('transfer_message', $message . '.'); return $this->redirect('/admin/vai-e-vem/' . $id);
    }
    private function itemForUser(array $user, int $id): ?array { $item = $this->transfers->find($id); return $item && $this->access->canManageChampionship($user, (int) $item['championship_id']) ? $item : null; }
    private function championships(array $user): array { return $this->transfers->championshipsForUser((int) $user['id'], $this->authorization->roleKeys($user), $this->access->isAdministrator($user)); }
    private function formPage(array $user, array $record, bool $editing, array $errors): Response { return $this->page($editing ? 'Editar movimentacao' : 'Nova movimentacao', 'admin/transfers/form', $this->formData($user, $record, $editing) + ['errors' => $errors]); }
    private function formData(array $user, array $record, bool $editing): array { $allowed = $this->access->allowedChampionshipIds($user); return ['user' => $user, 'record' => $record, 'editing' => $editing, 'championships' => $this->championships($user), 'athletes' => $this->transfers->athletesForChampionships($allowed), 'teams' => $this->transfers->teamsForChampionships($allowed), 'types' => TransferRepository::TYPES, 'errors' => []]; }
    private function redirect(string $path): Response { return Response::redirect(\App\Core\Config::url($path)); }
}
