<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ChampionshipRepository;
use App\Repositories\OfficialRepository;
use App\Services\StorageService;

final class OfficialController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly OfficialRepository $officials, private readonly ChampionshipRepository $championships, private readonly \App\Services\ChampionshipAccessService $access, private readonly StorageService $storage)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'championships.view'); if ($user instanceof Response) return $user;
        $championship = $this->championship($user, (int) ($request->query['championship_id'] ?? 0));
        $items = $championship ? $this->officials->listForChampionship((int) $championship['id']) : [];
        return $this->page('Árbitros e oficiais', 'admin/officials/index', ['user' => $user, 'championships' => $this->available($user), 'championship' => $championship, 'items' => $items, 'message' => Session::consumeFlash('official_message')]);
    }

    public function form(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'championships.view'); if ($user instanceof Response) return $user;
        $record = isset($params[0]) ? $this->officials->find((int) $params[0]) : null;
        if ($record && !$this->championship($user, (int) $record['championship_id'])) return Response::forbidden();
        return $this->page($record ? 'Editar árbitro' : 'Novo árbitro', 'admin/officials/form', ['user' => $user, 'record' => $record ?: ['championship_id' => (int) ($request->query['championship_id'] ?? 0), 'full_name' => '', 'public_name' => '', 'role' => 'referee', 'status' => 'active'], 'championships' => $this->available($user), 'errors' => []]);
    }

    public function save(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'championships.view'); if ($user instanceof Response) return $user;
        $existing = isset($params[0]) ? $this->officials->find((int) $params[0]) : null;
        if ($existing && !$this->championship($user, (int) $existing['championship_id'])) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('Sua sessão expirou.');
        $data = ['championship_id' => (int) ($request->body['championship_id'] ?? 0), 'full_name' => trim((string) ($request->body['full_name'] ?? '')), 'public_name' => trim((string) ($request->body['public_name'] ?? '')), 'role' => (string) ($request->body['role'] ?? 'referee'), 'status' => (string) ($request->body['status'] ?? 'active'), 'photo_path' => $existing['photo_path'] ?? null];
        $errors = [];
        if (!$this->championship($user, $data['championship_id'])) $errors[] = 'Selecione um campeonato permitido.';
        if (mb_strlen($data['full_name']) < 3 || mb_strlen($data['full_name']) > 180) $errors[] = 'Informe o nome completo.';
        if (!in_array($data['role'], ['referee', 'assistant', 'fourth_official', 'scorekeeper', 'coordinator'], true)) $errors[] = 'Função inválida.';
        if (!in_array($data['status'], ['active', 'inactive'], true)) $errors[] = 'Status inválido.';
        $newPhotoPath = null;
        if (isset($request->files['photo']) && ($request->files['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) try { $stored = $this->storage->storeOptimizedImage($request->files['photo'], 'officials/' . $data['championship_id'], ['max_width' => 1200, 'max_height' => 1200]); $data['photo_path'] = $stored['path']; $newPhotoPath = $stored['path']; } catch (\Throwable $exception) { $errors[] = $exception->getMessage(); }
        if ($errors !== [] && $newPhotoPath) $this->storage->delete($newPhotoPath);
        if ($errors !== []) return $this->errorPage($existing ? 'Editar árbitro' : 'Novo árbitro', 'admin/officials/form', ['user' => $user, 'record' => array_merge($existing ?: [], $data), 'championships' => $this->available($user), 'errors' => $errors], 422);
        if ($existing) { $this->officials->update((int) $existing['id'], $data); $id = (int) $existing['id']; $action = 'updated'; if ($newPhotoPath && !empty($existing['photo_path']) && $existing['photo_path'] !== $newPhotoPath) $this->storage->delete((string) $existing['photo_path']); } else { $id = $this->officials->create($data, (int) $user['id']); $action = 'created'; }
        $this->audit->record('official.' . $action, (int) $user['id'], 'championship_official', $id, ['championship_id' => $data['championship_id']], $request);
        Session::flash('official_message', 'Cadastro de arbitragem salvo.');
        return Response::redirect(Config::url('/admin/arbitros?championship_id=' . $data['championship_id']));
    }

    private function available(array $user): array { return $this->championships->listForUser((int) $user['id'], $this->access->isAdministrator($user)); }
    private function championship(array $user, int $id): ?array { return $id > 0 ? $this->championships->findForUser($id, (int) $user['id'], $this->access->isAdministrator($user)) : null; }
}
