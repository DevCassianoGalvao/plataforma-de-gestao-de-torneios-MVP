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
        $locked = $user && $user['locked_until'] !== null && strtotime((string) $user['locked_until']) > time();
        $validPassword = password_verify($password, $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCkR2c7QG8M0j2G9rM3u');
        $valid = $user && $user['status'] === 'active' && !$locked && $validPassword && $recent < $maxAttempts;

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
        Session::regenerate();
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

    private function recentFailures(string $emailHash, string $ip, int $minutes): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE successful = 0 AND attempted_at >= ? AND (email_hash = ? OR ip = ?)');
        $statement->execute([date('Y-m-d H:i:s', time() - $minutes * 60), $emailHash, $ip]);
        return (int) $statement->fetchColumn();
    }

    private function recordAttempt(string $emailHash, Request $request, bool $successful): void
    {
        $statement = $this->pdo->prepare('INSERT INTO login_attempts (email_hash, ip, user_agent, successful, attempted_at) VALUES (?, ?, ?, ?, ?)');
        $statement->execute([$emailHash, $request->ip(), $request->userAgent(), $successful ? 1 : 0, date('Y-m-d H:i:s')]);
    }
}
