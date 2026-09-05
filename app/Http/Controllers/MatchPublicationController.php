<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ScheduleRepository;
use App\Services\MatchPublicationService;

final class MatchPublicationController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly ScheduleRepository $schedules, private readonly MatchPublicationService $service)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function update(Request $request, array $params = []): Response
    {
        $user = $this->guard($request, 'match_publication.manage');
        if ($user instanceof Response) return $user;
        $roles = $this->authorization->roleKeys($user);
        $isAdmin = in_array('administrator', $roles, true);
        $isOrganizer = in_array('organizer', $roles, true);
        if (!$isAdmin && !$isOrganizer) return Response::forbidden();
        if (!$this->validCsrf($request)) return Response::forbidden('A sessão expirou.');
        $match = $isAdmin ? $this->schedules->matchById((int) ($params[0] ?? 0)) : $this->schedules->matchForUser((int) ($params[0] ?? 0), (int) $user['id'], 'championship');
        if (!$match) return Response::html('Partida não encontrada.', 404);
        $action = (string) ($request->body['action'] ?? '');
        $reason = trim((string) ($request->body['reason'] ?? ''));
        $result = match ($action) {
            'publish' => $this->service->publish($user, $match, $reason ?: null),
            'schedule' => $this->service->schedule($user, $match, (string) ($request->body['scheduled_at'] ?? ''), $reason ?: null),
            'cancel' => $this->service->cancel($user, $match, $reason),
            default => ['ok' => false, 'errors' => ['Ação de publicação inválida.']],
        };
        if (!$result['ok']) { Session::flash('schedule_message', implode(' ', $result['errors'])); }
        else { Session::flash('schedule_message', 'Publicação da partida atualizada.'); }
        return Response::redirect(Config::url('/admin/partidas/' . $match['id']));
    }
}
