<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\PasswordPolicy;
use App\Services\StorageService;

final class ProfileController extends Controller
{
    public function __construct($users, AuthorizationService $authorization, AuditService $audit, private readonly StorageService $storage)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function show(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) {
            return $guard;
        }
        return $this->page('Meu perfil', 'admin/profile', ['user' => $guard, 'message' => Session::consumeFlash('profile_message'), 'errors' => []]);
    }

    public function update(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) {
            return $guard;
        }
        if (!$this->csrf($request)) {
            return $this->errorPage('Meu perfil', 'admin/profile', ['user' => $guard, 'message' => null, 'errors' => ['A sessao expirou.']], 419);
        }
        $name = trim((string) ($request->body['name'] ?? ''));
        if (strlen($name) < 2) {
            return $this->errorPage('Meu perfil', 'admin/profile', ['user' => $guard, 'message' => null, 'errors' => ['Informe um nome valido.']], 422);
        }
        $this->users->update((int) $guard['id'], $name, (string) $guard['email']);
        $this->audit->record('profile.updated', (int) $guard['id'], 'user', (int) $guard['id'], [], $request);
        Session::flash('profile_message', 'Perfil atualizado.');
        return Response::redirect(Config::url('/admin/perfil'));
    }

    public function changePassword(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) {
            return $guard;
        }
        if (!$this->csrf($request)) {
            return $this->errorPage('Meu perfil', 'admin/profile', ['user' => $guard, 'message' => null, 'errors' => ['A sessao expirou.']], 419);
        }
        if (!password_verify((string) ($request->body['current_password'] ?? ''), (string) $guard['password_hash'])) {
            return $this->errorPage('Meu perfil', 'admin/profile', ['user' => $guard, 'message' => null, 'errors' => ['A senha atual esta incorreta.']], 422);
        }
        $errors = PasswordPolicy::validate((string) ($request->body['password'] ?? ''), (string) ($request->body['password_confirmation'] ?? ''));
        if ($errors !== []) {
            return $this->errorPage('Meu perfil', 'admin/profile', ['user' => $guard, 'message' => null, 'errors' => $errors], 422);
        }
        $this->users->updatePassword((int) $guard['id'], password_hash((string) $request->body['password'], PASSWORD_DEFAULT));
        $this->audit->record('profile.password_changed', (int) $guard['id'], 'user', (int) $guard['id'], [], $request);
        Session::flash('profile_message', 'Senha atualizada.');
        return Response::redirect(Config::url('/admin/perfil'));
    }

    public function uploadAvatar(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) {
            return $guard;
        }
        if (!$this->csrf($request)) {
            return $this->errorPage('Meu perfil', 'admin/profile', ['user' => $guard, 'message' => null, 'errors' => ['A sessão expirou.']], 419);
        }
        try {
            $stored = $this->storage->storeOptimizedImage($request->files['avatar'] ?? [], 'profiles/' . (int) $guard['id'], ['max_width' => 800, 'max_height' => 800]);
        } catch (\Throwable $exception) {
            return $this->errorPage('Meu perfil', 'admin/profile', ['user' => $guard, 'message' => null, 'errors' => ['Foto de perfil: ' . $exception->getMessage()]], 422);
        }
        $previousPath = (string) ($guard['avatar_path'] ?? '');
        $this->users->updateAvatar((int) $guard['id'], $stored['path']);
        if ($previousPath !== '') {
            $this->storage->delete($previousPath);
        }
        $this->audit->record('profile.avatar_updated', (int) $guard['id'], 'user', (int) $guard['id'], ['mime' => $stored['mime'], 'size' => $stored['size']], $request);
        Session::flash('profile_message', 'Foto de perfil atualizada.');
        return Response::redirect(Config::url('/admin/perfil'));
    }

    public function avatar(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request);
        if ($guard instanceof Response) {
            return $guard;
        }
        $file = $this->storage->read((string) ($guard['avatar_path'] ?? ''));
        if (!$file) {
            return Response::html('Foto não encontrada.', 404);
        }
        return Response::binary($file['body'], $file['mime'], $file['name']);
    }

    private function csrf(Request $request): bool
    {
        try {
            Security::verifyCsrf($request->body['_csrf'] ?? null);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
