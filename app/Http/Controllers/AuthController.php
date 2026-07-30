<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Services\AuthService;
use App\Services\AuthorizationService;
use App\Services\PasswordResetService;
use App\Services\AuditService;
use App\Repositories\UserRepository;

final class AuthController extends Controller
{
    public function __construct(UserRepository $users, AuthorizationService $authorization, AuditService $audit, private readonly AuthService $auth, private readonly PasswordResetService $passwordReset)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function login(Request $request, array $params = []): Response
    {
        if (Auth::authenticated()) {
            return Response::redirect(Config::url('/admin'));
        }
        return $this->page('Entrar', 'auth/login', ['next' => $request->query['next'] ?? '', 'message' => null, 'oldEmail' => '']);
    }

    public function authenticate(Request $request, array $params = []): Response
    {
        try {
            Security::verifyCsrf($request->body['_csrf'] ?? null);
        } catch (\Throwable) {
            return $this->errorPage('Entrar', 'auth/login', ['next' => $request->body['next'] ?? '', 'message' => 'A sessao expirou. Recarregue a pagina e tente novamente.', 'oldEmail' => $request->body['email'] ?? ''], 419);
        }
        $email = (string) ($request->body['email'] ?? '');
        $password = (string) ($request->body['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            return $this->errorPage('Entrar', 'auth/login', ['next' => $request->body['next'] ?? '', 'message' => 'Informe um e-mail e uma senha validos.', 'oldEmail' => $email], 422);
        }
        $result = $this->auth->attempt($email, $password, $request);
        if (!$result['ok']) {
            return $this->errorPage('Entrar', 'auth/login', ['next' => $request->body['next'] ?? '', 'message' => $result['message'], 'oldEmail' => $email], 422);
        }
        $user = $result['user'];
        $role = $this->authorization->primaryRole($user);
        $fallbacks = [
            'administrator' => '/admin',
            'organizer' => '/meus-campeonatos',
            'team_manager' => '/minha-equipe',
            'match_operator' => '/minhas-partidas',
            'accountability' => '/prestacao',
            'communication' => '/conteudo',
        ];
        $next = Security::safeLocalPath((string) ($request->body['next'] ?? ''));
        if ($next !== null) {
            return Response::redirect($next);
        }
        return Response::redirect(Config::url($fallbacks[$role] ?? '/admin'));
    }

    public function logout(Request $request, array $params = []): Response
    {
        try {
            Security::verifyCsrf($request->body['_csrf'] ?? null);
        } catch (\Throwable) {
            return Response::forbidden('Nao foi possivel validar a sessao.');
        }
        $this->auth->logout($request);
        return Response::redirect(Config::url('/login'));
    }

    public function forgot(Request $request, array $params = []): Response
    {
        return $this->page('Recuperar senha', 'auth/forgot', ['message' => null]);
    }

    public function requestReset(Request $request, array $params = []): Response
    {
        try {
            Security::verifyCsrf($request->body['_csrf'] ?? null);
        } catch (\Throwable) {
            return $this->errorPage('Recuperar senha', 'auth/forgot', ['message' => 'A sessao expirou. Recarregue a pagina e tente novamente.'], 419);
        }
        $email = (string) ($request->body['email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->passwordReset->request($email, $request);
        }
        return $this->page('Recuperar senha', 'auth/forgot', ['message' => 'Se o e-mail estiver cadastrado, enviaremos instrucoes para redefinir a senha.']);
    }

    public function resetForm(Request $request, array $params = []): Response
    {
        return $this->page('Nova senha', 'auth/reset', ['token' => (string) ($request->query['token'] ?? ''), 'message' => null]);
    }

    public function reset(Request $request, array $params = []): Response
    {
        try {
            Security::verifyCsrf($request->body['_csrf'] ?? null);
        } catch (\Throwable) {
            return $this->errorPage('Nova senha', 'auth/reset', ['token' => (string) ($request->body['token'] ?? ''), 'message' => 'A sessao expirou. Recarregue a pagina e tente novamente.'], 419);
        }
        $result = $this->passwordReset->reset((string) ($request->body['token'] ?? ''), (string) ($request->body['password'] ?? ''), (string) ($request->body['password_confirmation'] ?? ''), $request);
        if (!$result['ok']) {
            return $this->errorPage('Nova senha', 'auth/reset', ['token' => (string) ($request->body['token'] ?? ''), 'message' => $result['message']], 422);
        }
        return $this->page('Senha atualizada', 'auth/success', ['message' => 'Senha atualizada. Agora voce pode entrar.']);
    }
}
