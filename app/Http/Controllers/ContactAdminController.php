<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ContactRepository;

final class ContactAdminController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly ContactRepository $contacts)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'system.access');
        if ($user instanceof Response) return $user;
        if (!$this->administrator($user)) return Response::forbidden();
        return $this->page('Contatos recebidos', 'admin/contacts/index', ['user' => $user, 'items' => $this->contacts->list(), 'message' => Session::consumeFlash('contact_admin_message')]);
    }

    public function update(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'system.access');
        if ($user instanceof Response) return $user;
        if (!$this->administrator($user) || !$this->validCsrf($request)) return Response::forbidden('Sua sessão expirou.');
        $status = (string) ($request->body['status'] ?? '');
        if (!in_array($status, ['new', 'in_progress', 'resolved', 'archived'], true)) return Response::forbidden('Status inválido.');
        $id = (int) ($params[0] ?? 0);
        if ($this->contacts->updateStatus($id, $status, (int) $user['id'])) $this->audit->record('public_contact.status_updated', (int) $user['id'], 'public_contact_message', $id, ['status' => $status], $request);
        Session::flash('contact_admin_message', 'Situação do contato atualizada.');
        return Response::redirect(Config::url('/admin/contatos'));
    }

    private function administrator(array $user): bool { return in_array('administrator', $this->authorization->roleKeys($user), true); }
}
