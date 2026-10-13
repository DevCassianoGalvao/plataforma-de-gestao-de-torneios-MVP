<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\PlayerBanRepository;

final class PlayerBanController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly PlayerBanRepository $bans)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $user = $this->administrator($request);
        if ($user instanceof Response) return $user;
        return $this->page('Banidos', 'admin/bans/index', ['user' => $user, 'banned' => $this->bans->banned(), 'candidates' => $this->bans->candidates(), 'errors' => [], 'message' => Session::consumeFlash('ban_message')]);
    }

    public function ban(Request $request, array $params = []): Response
    {
        $user = $this->administrator($request);
        if ($user instanceof Response) return $user;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        [$athleteId, $championshipId] = array_map('intval', explode(':', (string) ($request->body['registration'] ?? '0:0')) + [0, 0]);
        $reason = trim((string) ($request->body['reason'] ?? ''));
        $date = (string) ($request->body['banned_on'] ?? '');
        $errors = [];
        if (!$athleteId || !$championshipId) $errors[] = 'Selecione o jogador.';
        if ($reason === '') $errors[] = 'Informe o motivo do banimento.';
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed) $errors[] = 'Informe a data do banimento.';
        if ($errors === [] && !$this->bans->banAllowed($athleteId, $championshipId)) $errors[] = 'O regulamento publicado deste campeonato nao permite banimento de jogadores.';
        if ($errors === [] && !$this->bans->ban($athleteId, $championshipId, $parsed->format('Y-m-d') . ' ' . date('H:i:s'), mb_substr($reason, 0, 500), (int) $user['id'])) $errors[] = 'Jogador nao encontrado ou ja banido.';
        if ($errors !== []) return $this->errorPage('Banidos', 'admin/bans/index', ['banned' => $this->bans->banned(), 'candidates' => $this->bans->candidates(), 'errors' => $errors], 422);
        $this->audit->record('athletes.banned', (int) $user['id'], 'athlete', $athleteId, ['championship_id' => $championshipId, 'reason' => $reason, 'date' => $parsed->format('Y-m-d')], $request);
        Session::flash('ban_message', 'Jogador banido.');
        return Response::redirect(Config::url('/admin/banidos'));
    }

    public function unban(Request $request, array $params = []): Response
    {
        $user = $this->administrator($request);
        if ($user instanceof Response) return $user;
        if (!$this->validCsrf($request)) return Response::forbidden('A sessao expirou.');
        $athleteId = (int) ($params[0] ?? 0);
        $previous = $this->bans->unban($athleteId);
        if (!$previous) return Response::html('Jogador banido nao encontrado.', 404);
        $this->audit->record('athletes.unbanned', (int) $user['id'], 'athlete', $athleteId, ['previous_reason' => $previous['banned_reason'], 'previous_date' => $previous['banned_at']], $request);
        Session::flash('ban_message', 'Banimento desfeito: ' . $previous['full_name'] . '.');
        return Response::redirect(Config::url('/admin/banidos'));
    }

    private function administrator(Request $request): array|Response
    {
        $user = $this->guard($request);
        if ($user instanceof Response) return $user;
        if (!in_array('administrator', $this->authorization->roleKeys($user), true)) return Response::forbidden('Somente administradores podem gerenciar banimentos.');
        return $user;
    }
}
