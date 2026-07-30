<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\NotificationRepository;

final class NotificationController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly NotificationRepository $notifications)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'system.access');
        if ($user instanceof Response) return $user;
        if (!$this->isAdministrator($user)) return Response::forbidden();
        return $this->page('Notificacoes', 'admin/notifications/index', ['user' => $user, 'items' => $this->notifications->listForUser((int) $user['id']), 'unreadCount' => $this->notifications->unreadCount((int) $user['id']), 'message' => Session::consumeFlash('notification_message')]);
    }

    public function read(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'system.access');
        if ($user instanceof Response) return $user;
        if (!$this->isAdministrator($user) || !$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $this->notifications->markRead((int) ($params[0] ?? 0), (int) $user['id']);
        return Response::redirect(Config::url('/admin/notificacoes'));
    }

    public function readAll(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'system.access');
        if ($user instanceof Response) return $user;
        if (!$this->isAdministrator($user) || !$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $this->notifications->markAllRead((int) $user['id']);
        Session::flash('notification_message', 'Todas as notificacoes foram marcadas como lidas.');
        return Response::redirect(Config::url('/admin/notificacoes'));
    }

    private function isAdministrator(array $user): bool
    {
        return in_array('administrator', $this->authorization->roleKeys($user), true);
    }
}
