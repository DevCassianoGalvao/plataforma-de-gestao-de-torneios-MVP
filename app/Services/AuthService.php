<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Request;
use App\Core\Security;
use App\Core\Session;
use App\Repositories\UserRepository;
use PDO;

final class AuthService
{
    public function __construct(private readonly PDO $pdo, private readonly UserRepository $users, private readonly AuditService $audit)
    {
    }

    public function attempt(string $email, string $password, Request $request): array
    {
        $email = strtolower(trim($email));
        $emailHash = Security::hashToken($email);
        $user = $this->users->findByEmail($email);
        $now = date('Y-m-d H:i:s');
        $maxAttempts = max(3, (int) (Config::get('AUTH_MAX_ATTEMPTS', '5') ?? '5'));
        $windowMinutes = max(1, (int) (Config::get('AUTH_WINDOW_MINUTES', '15') ?? '15'));
        $recent = $this->recentFailures($emailHash, $request->ip(), $windowMinutes);
        $emailFailures = $recent['email'];
        // A shared office/cPanel IP must not lock every account after one
        // person's mistakes. Keep a broader IP limit for abuse protection.
        $ipFailures = $recent['ip'];
        $ipLimit = $maxAttempts * 5;
        $locked = $user && $user['locked_until'] !== null && strtotime((string) $user['locked_until']) > time();
        $validPassword = password_verify($password, $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCkR2c7QG8M0j2G9rM3u');
        $valid = $user && $user['status'] === 'active' && !$locked && $validPassword && $emailFailures < $maxAttempts && $ipFailures < $ipLimit;

        $this->recordAttempt($emailHash, $request, $valid);
        if (!$valid) {
            if ($user && $user['status'] === 'active' && !$locked) {
                $attempts = (int) $user['failed_login_attempts'] + 1;
                $lockSeconds = max(60, (int) (Config::get('AUTH_LOCK_SECONDS', '900') ?? '900'));
                $lockUntil = $attempts >= $maxAttempts ? date('Y-m-d H:i:s', time() + $lockSeconds) : null;
                $this->users->markFailedLogin((int) $user['id'], $attempts, $lockUntil);
            }
            if ($user) {
                $this->audit->record('auth.login_failed', (int) $user['id'], 'user', (int) $user['id'], [], $request);
            } else {
                $this->audit->record('auth.login_failed', null, 'user', null, [], $request);
            }
            return ['ok' => false, 'message' => 'E-mail ou senha invalidos.'];
        }

        $this->users->markSuccessfulLogin((int) $user['id']);
        $this->users->clearFailedLoginAttempts($emailHash, $request->ip());
        Session::regenerate();
        Security::rotateCsrf();
        Session::put('user_id', (int) $user['id']);
        Session::put('last_activity', time());
        $this->audit->record('auth.login_succeeded', (int) $user['id'], 'user', (int) $user['id'], [], $request);
        return ['ok' => true, 'user' => $user];
    }

    public function logout(?Request $request = null): void
    {
        $userId = Session::get('user_id');
        if (is_int($userId) || ctype_digit((string) $userId)) {
            $this->audit->record('auth.logout', (int) $userId, 'user', (int) $userId, [], $request);
        }
        Session::destroy();
    }

    private function recentFailures(string $emailHash, string $ip, int $minutes): array
    {
        $since = date('Y-m-d H:i:s', time() - $minutes * 60);
        $statement = $this->pdo->prepare('SELECT SUM(email_hash = ?) AS email_failures, SUM(ip = ?) AS ip_failures FROM login_attempts WHERE successful = 0 AND attempted_at >= ?');
        $statement->execute([$emailHash, $ip, $since]);
        $row = $statement->fetch() ?: [];
        return ['email' => (int) ($row['email_failures'] ?? 0), 'ip' => (int) ($row['ip_failures'] ?? 0)];
    }

    private function recordAttempt(string $emailHash, Request $request, bool $successful): void
    {
        $statement = $this->pdo->prepare('INSERT INTO login_attempts (email_hash, ip, user_agent, successful, attempted_at) VALUES (?, ?, ?, ?, ?)');
        $statement->execute([$emailHash, $request->ip(), $request->userAgent(), $successful ? 1 : 0, date('Y-m-d H:i:s')]);
    }
}
