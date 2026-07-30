<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\MatchMediaRepository;
use App\Services\MatchOperationAccessService;
use App\Services\StorageService;

final class MatchMediaController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly MatchMediaRepository $media, private readonly MatchOperationAccessService $access, private readonly StorageService $storage)
    { parent::__construct($users, $authorization, $audit); }

    public function index(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'match_operation.view'); if ($user instanceof Response) return $user;
        $match = $this->access->matchForUser($user, (int) ($params[0] ?? 0)); if (!$match || !$this->access->canView($user, $match)) return Response::forbidden();
        return $this->page('Evidencias da partida', 'admin/matches/media', ['user' => $user, 'match' => $match, 'items' => $this->media->list((int) $match['id']), 'canUpload' => $this->access->canOperate($user, $match), 'message' => Session::consumeFlash('media_message'), 'errors' => []]);
    }

    public function upload(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'match_operation.operate'); if ($user instanceof Response) return $user;
        $match = $this->access->matchForUser($user, (int) ($params[0] ?? 0)); if (!$match || !$this->access->canOperate($user, $match)) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $stored = null;
        try {
            $stored = $this->storage->storeOptimizedImage($request->files['photo'] ?? [], 'match-media/' . (int) $match['id'], ['max_width' => 1920, 'max_height' => 1440]);
            $id = $this->media->create(['match_id' => $match['id'], 'championship_id' => $match['championship_id'], 'title' => trim((string) ($request->body['title'] ?? '')) ?: 'Registro da partida', 'caption' => trim((string) ($request->body['caption'] ?? '')) ?: null, 'storage_path' => $stored['path'], 'original_name' => $stored['original_name'], 'mime_type' => $stored['mime'], 'visibility' => in_array($request->body['visibility'] ?? '', ['private', 'accountability', 'public'], true) ? $request->body['visibility'] : 'accountability', 'captured_at' => trim((string) ($request->body['captured_at'] ?? '')) ?: null, 'uploaded_by' => $user['id']]);
        } catch (\Throwable $exception) { if ($stored) $this->storage->delete($stored['path']); return Response::html('Nao foi possivel enviar a evidencia: ' . $exception->getMessage(), 422); }
        $this->audit->record('match_media.uploaded', (int) $user['id'], 'match_media', $id, ['match_id' => (int) $match['id'], 'mime' => $stored['mime'], 'size' => $stored['size']], $request);
        Session::flash('media_message', 'Evidencia enviada.'); return Response::redirect(Config::url('/admin/partidas/' . $match['id'] . '/evidencias'));
    }

    public function asset(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'match_operation.view'); if ($user instanceof Response) return $user;
        $item = $this->media->find((int) ($params[1] ?? 0)); if (!$item) return Response::html('Arquivo nao encontrado.', 404);
        $match = $this->access->matchForUser($user, (int) $item['match_id']); if (!$match || !$this->access->canView($user, $match)) return Response::forbidden();
        $file = $this->storage->read((string) $item['storage_path']); if (!$file) return Response::html('Arquivo nao encontrado.', 404);
        return Response::binary($file['body'], $file['mime'], $item['original_name']);
    }
}
