<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\GovernanceRepository;

final class GovernanceController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly GovernanceRepository $governance) { parent::__construct($users, $authorization, $audit); }
    public function index(Request $request, array $params = []): Response { $user = $this->guard($request, 'system.configure'); if ($user instanceof Response) return $user; return $this->page('Organizacoes e projetos', 'admin/governance/index', ['user' => $user, 'organizations' => $this->governance->organizations(), 'projects' => $this->governance->projects(), 'message' => Session::consumeFlash('governance_message')]); }
    public function organization(Request $request, array $params = []): Response { $user = $this->guard($request, 'system.configure'); if ($user instanceof Response) return $user; if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.'); $name = trim((string) ($request->body['name'] ?? '')); $slug = trim((string) ($request->body['slug'] ?? '')); if (strlen($name) < 2 || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) return Response::html('Nome ou slug invalido.', 422); try { $this->governance->createOrganization($name, $slug, (int) $user['id']); } catch (\Throwable) { return Response::html('Organizacao ja existe ou nao pode ser criada.', 422); } $this->audit->record('organization.created', (int) $user['id'], 'organization', null, ['slug' => $slug], $request); Session::flash('governance_message', 'Organizacao criada.'); return Response::redirect(Config::url('/admin/governanca')); }
    public function project(Request $request, array $params = []): Response { $user = $this->guard($request, 'system.configure'); if ($user instanceof Response) return $user; if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.'); $organizationId = (int) ($request->body['organization_id'] ?? 0); $name = trim((string) ($request->body['name'] ?? '')); $slug = trim((string) ($request->body['slug'] ?? '')); if (!$organizationId || strlen($name) < 2 || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1) return Response::html('Dados do projeto invalidos.', 422); try { $this->governance->createProject($organizationId, $name, $slug, (int) $user['id']); } catch (\Throwable) { return Response::html('Projeto nao pode ser criado.', 422); } $this->audit->record('project.created', (int) $user['id'], 'project', null, ['organization_id' => $organizationId, 'slug' => $slug], $request); Session::flash('governance_message', 'Projeto criado.'); return Response::redirect(Config::url('/admin/governanca')); }
}
