<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\BackupRepository;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\BackupService;

final class BackupController extends Controller
{
    public function __construct(UserRepository $users, AuthorizationService $authorization, AuditService $audit, private readonly BackupRepository $backups, private readonly BackupService $service) { parent::__construct($users, $authorization, $audit); }
    public function index(Request $request, array $params = []): Response { $user = $this->guard($request, 'backup.view'); if ($user instanceof Response) return $user; return $this->page('Backups', 'admin/backups/index', ['user' => $user, 'backups' => $this->backups->list(), 'remoteConfigured' => Config::get('BACKUP_STORAGE_PROVIDER', 'local') === 'google_drive']); }
    public function run(Request $request, array $params = []): Response { $user = $this->guard($request, 'backup.run'); if ($user instanceof Response) return $user; if (!$this->validCsrf($request)) return Response::forbidden(); try { $this->service->run((int) $user['id']); $_SESSION['flash_success'] = 'Backup criado e validado.'; } catch (\Throwable $e) { $_SESSION['flash_error'] = 'Backup nao concluido: ' . $e->getMessage(); } return $this->redirect(); }
    public function test(Request $request, array $params = []): Response { $user = $this->guard($request, 'backup.configure'); if ($user instanceof Response) return $user; if (!$this->validCsrf($request)) return Response::forbidden(); $result = $this->service->testRemote(); $_SESSION[($result['ok'] ?? false) ? 'flash_success' : 'flash_error'] = ($result['ok'] ?? false) ? 'Conexao remota validada.' : ('Conexao remota indisponivel: ' . ($result['error'] ?? 'erro desconhecido.')); return $this->redirect(); }
    public function retry(Request $request, array $params = []): Response { $user = $this->guard($request, 'backup.retry'); if ($user instanceof Response) return $user; if (!$this->validCsrf($request)) return Response::forbidden(); try { $this->service->retryRemote((int) ($params['id'] ?? 0), (int) $user['id']); $_SESSION['flash_success'] = 'Envio remoto processado.'; } catch (\Throwable $e) { $_SESSION['flash_error'] = $e->getMessage(); } return $this->redirect(); }
    public function delete(Request $request, array $params = []): Response { $user = $this->guard($request, 'backup.delete'); if ($user instanceof Response) return $user; if (!$this->validCsrf($request)) return Response::forbidden(); if (($request->body['confirmacao'] ?? '') !== 'EXCLUIR') { $_SESSION['flash_error'] = 'Digite EXCLUIR para confirmar.'; return $this->redirect(); } try { $this->service->delete((int) ($params['id'] ?? 0), (int) $user['id']); $_SESSION['flash_success'] = 'Backup excluido.'; } catch (\Throwable $e) { $_SESSION['flash_error'] = $e->getMessage(); } return $this->redirect(); }
    public function download(Request $request, array $params = []): Response { $user = $this->guard($request, 'backup.download'); if ($user instanceof Response) return $user; try { [$backup, $path] = $this->service->file((int) ($params['id'] ?? 0)); $this->audit->record('backup.downloaded', (int) $user['id'], 'application_backup', (string) $backup['id'], [], $request); return new Response(200, ['Content-Type' => 'application/zip', 'Content-Length' => (string) filesize($path), 'Content-Disposition' => 'attachment; filename="' . basename($path) . '"', 'X-Content-Type-Options' => 'nosniff'], file_get_contents($path) ?: ''); } catch (\Throwable) { return Response::notFound(); } }
    private function redirect(): Response { return Response::redirect(Config::url('/admin/backups')); }
}
