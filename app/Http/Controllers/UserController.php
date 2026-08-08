<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthorizationService;
use App\Services\PasswordPolicy;

final class UserController extends Controller
{
    public function __construct(UserRepository $users, AuthorizationService $authorization, AuditService $audit)
    {
        parent::__construct($users, $authorization, $audit);
    }

    public function index(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.view');
        if ($guard instanceof Response) {
            return $guard;
        }
        $flash = Session::consumeFlash('admin_message');
        return $this->page('Usuarios', 'admin/users/index', ['user' => $guard, 'usersList' => $this->withRoles($this->users->list((string) ($request->query['q'] ?? ''))), 'roles' => $this->users->rolesCatalog(), 'canResetPassword' => $this->authorization->can($guard, 'users.update'), 'canDeleteUsers' => $this->authorization->can($guard, 'users.delete'), 'query' => (string) ($request->query['q'] ?? ''), 'message' => $flash]);
    }

    public function createForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.create');
        if ($guard instanceof Response) {
            return $guard;
        }
        return $this->page('Novo usuario', 'admin/users/form', ['mode' => 'create', 'user' => $guard, 'record' => ['name' => '', 'email' => '', 'status' => 'active'], 'roles' => $this->users->rolesCatalog(), 'selectedRoles' => [], 'errors' => [], 'message' => null]);
    }

    public function create(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.create');
        if ($guard instanceof Response) {
            return $guard;
        }
        if (!$this->validCsrf($request)) {
            return $this->errorForm('create', $guard, $request, ['A sessao expirou. Recarregue a pagina.']);
        }
        $record = ['name' => trim((string) ($request->body['name'] ?? '')), 'email' => strtolower(trim((string) ($request->body['email'] ?? ''))), 'status' => (string) ($request->body['status'] ?? 'active')];
        $errors = $this->validateRecord($record, (string) ($request->body['password'] ?? ''), (string) ($request->body['password_confirmation'] ?? ''));
        if ($errors !== []) {
            return $this->errorForm('create', $guard, $request, $errors, $record);
        }
        try {
            $id = $this->users->create($record['name'], $record['email'], password_hash((string) $request->body['password'], PASSWORD_DEFAULT), $record['status']);
            $this->syncRoles($id, $request, (int) $guard['id']);
            $this->audit->record('users.created', (int) $guard['id'], 'user', $id, ['email_hash' => hash('sha256', $record['email'])], $request);
        } catch (\PDOException) {
            return $this->errorForm('create', $guard, $request, ['Nao foi possivel criar o usuario. Verifique se o e-mail ja esta em uso.'], $record);
        }
        Session::flash('admin_message', 'Usuario criado com sucesso.');
        return Response::redirect(Config::url('/admin/usuarios'));
    }

    public function editForm(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.update');
        if ($guard instanceof Response) {
            return $guard;
        }
        $record = $this->users->findById((int) ($params[0] ?? 0));
        if (!$record) {
            return Response::html('Usuario nao encontrado.', 404);
        }
        return $this->page('Editar usuario', 'admin/users/form', ['mode' => 'edit', 'user' => $guard, 'record' => $record, 'roles' => $this->users->rolesCatalog(), 'selectedRoles' => $this->users->roleIds((int) $record['id']), 'errors' => [], 'message' => null]);
    }

    public function update(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.update');
        if ($guard instanceof Response) {
            return $guard;
        }
        $id = (int) ($params[0] ?? 0);
        $record = $this->users->findById($id);
        if (!$record) {
            return Response::html('Usuario nao encontrado.', 404);
        }
        if (!$this->validCsrf($request)) {
            return $this->errorForm('edit', $guard, $request, ['A sessao expirou. Recarregue a pagina.'], $record, $id);
        }
        $updated = ['id' => $id, 'name' => trim((string) ($request->body['name'] ?? '')), 'email' => strtolower(trim((string) ($request->body['email'] ?? ''))), 'status' => $record['status']];
        $errors = $this->validateRecord($updated);
        if ($errors !== []) {
            return $this->errorForm('edit', $guard, $request, $errors, $updated, $id);
        }
        try {
            $this->users->update($id, $updated['name'], $updated['email']);
            $this->audit->record('users.updated', (int) $guard['id'], 'user', $id, [], $request);
            if (array_key_exists('role_ids', $request->body)) {
                if ($this->authorization->cannot($guard, 'users.manage_roles')) {
                    return Response::forbidden();
                }
                $this->syncRoles($id, $request, (int) $guard['id']);
                $this->audit->record('users.roles_changed', (int) $guard['id'], 'user', $id, ['role_count' => count((array) $request->body['role_ids'])], $request);
            }
        } catch (\PDOException) {
            return $this->errorForm('edit', $guard, $request, ['Nao foi possivel salvar. Verifique se o e-mail ja esta em uso.'], $updated, $id);
        }
        Session::flash('admin_message', 'Usuario atualizado com sucesso.');
        return Response::redirect(Config::url('/admin/usuarios'));
    }

    public function status(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.deactivate');
        if ($guard instanceof Response) {
            return $guard;
        }
        if (!$this->validCsrf($request)) {
            return Response::forbidden('A sessao expirou.');
        }
        $id = (int) ($params[0] ?? 0);
        $status = (string) ($request->body['status'] ?? '');
        if (!$this->users->findById($id) || !in_array($status, ['active', 'inactive', 'blocked'], true)) {
            return Response::html('Usuario ou status invalido.', 422);
        }
        if ($id === (int) $guard['id'] && $status !== 'active') {
            return Response::html('O administrador atual nao pode bloquear a propria conta nesta tela.', 422);
        }
        $this->users->updateStatus($id, $status);
        $this->audit->record('users.status_changed', (int) $guard['id'], 'user', $id, ['status' => $status], $request);
        Session::flash('admin_message', 'Status atualizado.');
        return Response::redirect(Config::url('/admin/usuarios'));
    }

    public function roles(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.manage_roles');
        if ($guard instanceof Response) {
            return $guard;
        }
        if (!$this->validCsrf($request)) {
            return Response::forbidden('A sessao expirou.');
        }
        $id = (int) ($params[0] ?? 0);
        if (!$this->users->findById($id)) {
            return Response::html('Usuario nao encontrado.', 404);
        }
        $this->users->syncRoles($id, (array) ($request->body['role_ids'] ?? []), (int) $guard['id']);
        $this->audit->record('users.roles_changed', (int) $guard['id'], 'user', $id, ['role_count' => count((array) ($request->body['role_ids'] ?? []))], $request);
        Session::flash('admin_message', 'Perfis atualizados.');
        return Response::redirect(Config::url('/admin/usuarios'));
    }

    public function resetPassword(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.update');
        if ($guard instanceof Response) {
            return $guard;
        }
        if (!$this->validCsrf($request)) {
            return Response::forbidden('A sessao expirou.');
        }
        $record = $this->users->findById((int) ($params[0] ?? 0));
        if (!$record) {
            return Response::html('Usuario nao encontrado.', 404);
        }
        $temporaryPassword = $this->temporaryPassword();
        $this->users->updatePassword((int) $record['id'], password_hash($temporaryPassword, PASSWORD_DEFAULT));
        $this->audit->record('users.password_reset_generated', (int) $guard['id'], 'user', (int) $record['id'], [], $request);
        Session::flash('admin_message', 'Nova senha temporaria para ' . $record['name'] . ': ' . $temporaryPassword . '. Entregue a senha ao usuario e oriente a troca-la em Meu perfil.');
        return Response::redirect(Config::url('/admin/usuarios'));
    }

    public function delete(Request $request, array $params = []): Response
    {
        $guard = $this->guard($request, 'users.delete');
        if ($guard instanceof Response) {
            return $guard;
        }
        if (!$this->validCsrf($request)) {
            return Response::forbidden('A sessao expirou.');
        }
        $id = (int) ($params[0] ?? 0);
        $record = $this->users->findById($id);
        if (!$record) {
            return Response::html('Usuario nao encontrado.', 404);
        }
        if ($id === (int) $guard['id']) {
            return Response::html('A conta atualmente conectada nao pode ser excluida.', 422);
        }
        if (in_array('administrator', $this->authorization->roleKeys($record), true) && !$this->users->hasAnotherActiveAdministrator($id)) {
            return Response::html('Mantenha pelo menos um administrador ativo no sistema.', 422);
        }
        if (!$this->users->softDelete($id)) {
            return Response::html('Nao foi possivel excluir o usuario.', 422);
        }
        $this->audit->record('users.deleted', (int) $guard['id'], 'user', $id, ['email_hash' => hash('sha256', (string) $record['email'])], $request);
        Session::flash('admin_message', 'Usuario excluido com sucesso. Os historicos foram preservados.');
        return Response::redirect(Config::url('/admin/usuarios'));
    }

    private function temporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $digits = '23456789';
        $characters = [
            $letters[random_int(0, strlen($letters) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];
        for ($index = count($characters); $index < 12; $index++) {
            $characters[] = $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        for ($index = count($characters) - 1; $index > 0; $index--) {
            $swap = random_int(0, $index);
            [$characters[$index], $characters[$swap]] = [$characters[$swap], $characters[$index]];
        }
        return implode('', $characters);
    }

    private function validateRecord(array $record, ?string $password = null, ?string $confirmation = null): array
    {
        $errors = [];
        if (strlen((string) $record['name']) < 2) {
            $errors[] = 'Informe um nome valido.';
        }
        if (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Informe um e-mail valido.';
        }
        if (!in_array($record['status'] ?? 'active', ['active', 'inactive', 'blocked', 'pending'], true)) {
            $errors[] = 'Status de usuario invalido.';
        }
        if ($password !== null) {
            $errors = array_merge($errors, PasswordPolicy::validate($password, (string) $confirmation));
        }
        return $errors;
    }

    private function errorForm(string $mode, array $guard, Request $request, array $errors, array $record = [], int $id = 0): Response
    {
        return $this->errorPage('Usuario', 'admin/users/form', ['mode' => $mode, 'user' => $guard, 'record' => $record, 'roles' => $this->users->rolesCatalog(), 'selectedRoles' => array_map('intval', (array) ($request->body['role_ids'] ?? [])), 'errors' => $errors, 'message' => null, 'actionId' => $id], 422);
    }

    private function syncRoles(int $id, Request $request, int $createdBy): void
    {
        $this->users->syncRoles($id, (array) ($request->body['role_ids'] ?? []), $createdBy);
    }

    private function withRoles(array $users): array
    {
        foreach ($users as &$user) {
            $user['roles'] = $this->users->roles((int) $user['id']);
        }
        return $users;
    }
}
