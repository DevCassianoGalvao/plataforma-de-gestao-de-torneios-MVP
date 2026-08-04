<?php
declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Database;
use App\Core\Request;
use App\Core\Security;
use App\Repositories\UserRepository;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\AuthorizationService;
use App\Services\MailService;
use App\Services\PasswordResetService;
use App\Database\AuthSeed;
use function Tests\assert_same;
use function Tests\assert_true;

final class AuthIntegrationTest
{
    public static function run(): void
    {
        $pdo = Database::connection();
        $password = getenv('SEED_DEMO_PASSWORD') ?: 'TestDemo123';
        AuthSeed::run($pdo, $password);
        AuthSeed::run($pdo, $password);
        assert_same(6, (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(), 'Seed duplicou usuarios');
        assert_same(4, (int) $pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn(), 'Seed duplicou perfis');
        assert_same(154, (int) $pdo->query('SELECT COUNT(*) FROM permissions')->fetchColumn(), 'Seed duplicou permissoes');
        $users = new UserRepository($pdo);
        $admin = $users->findByEmail('admin@torneios.local');
        assert_true($admin !== null && password_verify($password, $admin['password_hash']), 'Senha seed nao pode ser verificada');
        $users->updateAvatar((int) $admin['id'], 'profiles/' . (int) $admin['id'] . '/avatar.png');
        assert_same('profiles/' . (int) $admin['id'] . '/avatar.png', (string) $users->findById((int) $admin['id'])['avatar_path'], 'Avatar de perfil nao foi atualizado');
        $users->updateAvatar((int) $admin['id'], '');
        assert_true(in_array('users.manage_roles', $users->permissions((int) $admin['id']), true), 'Permissao de administrador ausente');
        assert_true(in_array('match_publication.manage', $users->permissions((int) $admin['id']), true), 'Permissao de publicacao de partida ausente');
        assert_true(in_array('simulation.manage', $users->permissions((int) $admin['id']), true), 'Permissao de simulacao ausente');
        $teamManager = $users->findByEmail('treinador@torneios.local');
        assert_true(!in_array('users.view', $users->permissions((int) $teamManager['id']), true), 'Treinador recebeu permissao administrativa');
        try {
            $users->create('Duplicado', 'admin@torneios.local', password_hash($password, PASSWORD_DEFAULT));
            throw new \RuntimeException('E-mail duplicado foi aceito');
        } catch (\PDOException) {
            // Unicidade do e-mail validada pelo banco.
        }
        $auth = new AuthService($pdo, $users, new AuditService($pdo));
        $users->updateStatus((int) $teamManager['id'], 'inactive');
        assert_true($auth->attempt('treinador@torneios.local', $password, Request::fake('POST', '/login'))['ok'] === false, 'Usuario inativo autenticou');
        $users->updateStatus((int) $teamManager['id'], 'blocked');
        assert_true($auth->attempt('treinador@torneios.local', $password, Request::fake('POST', '/login'))['ok'] === false, 'Usuario bloqueado autenticou');
        $users->updateStatus((int) $teamManager['id'], 'active');
        putenv('AUTH_MAX_ATTEMPTS=3');
        putenv('AUTH_LOCK_SECONDS=60');
        $limitId = $users->create('Rate Limit', 'rate-limit@torneios.local', password_hash($password, PASSWORD_DEFAULT));
        for ($index = 0; $index < 3; $index++) {
            $auth->attempt('rate-limit@torneios.local', 'senha-errada', Request::fake('POST', '/login'));
        }
        assert_true($auth->attempt('rate-limit@torneios.local', $password, Request::fake('POST', '/login'))['ok'] === false, 'Rate limit nao bloqueou tentativa');
        assert_true($users->findById($limitId)['locked_until'] !== null, 'Rate limit nao registrou lock');
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$limitId]);
        $pdo->exec('DELETE FROM login_attempts');
        putenv('AUTH_MAX_ATTEMPTS');
        putenv('AUTH_LOCK_SECONDS');
        $mail = new MailService();
        putenv('MAIL_TRANSPORT=test');
        MailService::resetTestMessages();
        $audit = new AuditService($pdo);
        $reset = new PasswordResetService($pdo, $users, $audit, $mail);
        $request = Request::fake('POST', '/torneio-online/senha/esqueci', ['email' => 'admin@torneios.local']);
        $reset->request('admin@torneios.local', $request);
        $url = MailService::$testMessages[0]['url'] ?? '';
        preg_match('/token=([^&]+)/', $url, $matches);
        $token = urldecode($matches[1] ?? '');
        assert_true($token !== '', 'Token de recuperacao nao capturado no mailer de teste');
        $result = $reset->reset($token, 'NovaSenha123', 'NovaSenha123', $request);
        assert_true($result['ok'] === true, 'Redefinicao de senha falhou');
        assert_true($reset->reset($token, 'NovaSenha123', 'NovaSenha123', $request)['ok'] === false, 'Token de recuperacao reutilizavel');
        $expiredHash = Security::hashToken('expired-token');
        $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)')->execute([(int) $admin['id'], $expiredHash, date('Y-m-d H:i:s', time() - 60), date('Y-m-d H:i:s')]);
        assert_true($reset->reset('expired-token', 'NovaSenha123', 'NovaSenha123', $request)['ok'] === false, 'Token expirado foi aceito');
        assert_true((int) $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action LIKE 'auth.password_reset%'")->fetchColumn() >= 2, 'Auditoria de recuperacao ausente');
        $users->updatePassword((int) $admin['id'], password_hash($password, PASSWORD_DEFAULT));
    }
}
