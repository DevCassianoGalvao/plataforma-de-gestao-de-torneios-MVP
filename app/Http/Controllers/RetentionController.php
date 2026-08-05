<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\RetentionRepository;
use App\Services\RetentionService;

final class RetentionController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly RetentionRepository $repository, private readonly RetentionService $service) { parent::__construct($users, $authorization, $audit); }

    public function index(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'retention.view');
        if ($user instanceof Response) return $user;
        return $this->page('Retenção e arquivamento', 'admin/retention/index', ['user' => $user, 'policies' => $this->repository->policies(), 'actions' => $this->repository->actions(), 'message' => Session::consumeFlash('retention_message')]);
    }

    public function save(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'retention.manage');
        if ($user instanceof Response || !$this->validCsrf($request)) return $user instanceof Response ? $user : Response::forbidden('A sessão expirou.');
        $scope = (string) ($params[0] ?? '');
        $this->repository->savePolicy($scope, (array) $request->body, (int) $user['id']);
        $this->audit->record('retention.policy_updated', (int) $user['id'], 'retention_policy', $scope, [], $request);
        Session::flash('retention_message', 'Política de retenção atualizada.');
        return Response::redirect(Config::url('/admin/retencao'));
    }

    public function archive(Request $request, array $params = []): Response { return $this->mutate($request, $params, 'archive'); }
    public function restore(Request $request, array $params = []): Response { return $this->mutate($request, $params, 'restore'); }

    private function mutate(Request $request, array $params, string $action): Response
    {
        $user = $this->guard($request, 'retention.' . $action);
        if ($user instanceof Response || !$this->validCsrf($request)) return $user instanceof Response ? $user : Response::forbidden('A sessão expirou.');
        $entity = (string) ($params[0] ?? ''); $id = (int) ($params[1] ?? 0); $reason = trim((string) ($request->body['reason'] ?? ''));
        try {
            $result = $this->service->{$action}($entity, $id, (int) $user['id'], $reason);
        } catch (\InvalidArgumentException $error) {
            return $this->errorPage('Retenção', 'errors/500', ['message' => $error->getMessage()], 422);
        }
        if (!$result['ok']) return $this->errorPage('Retenção', 'errors/500', ['message' => $result['errors'][0]], 422);
        Session::flash('retention_message', $action === 'archive' ? 'Registro arquivado.' : 'Registro restaurado.');
        return Response::redirect(Config::url('/admin/retencao'));
    }
}
