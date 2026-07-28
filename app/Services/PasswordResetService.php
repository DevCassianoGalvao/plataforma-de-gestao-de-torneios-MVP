<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Request;
use App\Core\Security;
use App\Repositories\UserRepository;
use PDO;

final class PasswordResetService
{
    public function __construct(private readonly PDO $pdo, private readonly UserRepository $users, private readonly AuditService $audit, private readonly MailService $mail)
    {
    }

    public function request(string $email, Request $request): void
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));
        if (!$user || $user['status'] !== 'active') {
            $this->audit->record('auth.password_reset_requested', null, 'user', null, [], $request);
            return;
        }
        $token = Security::randomToken();
        $statement = $this->pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)');
        $statement->execute([(int) $user['id'], Security::hashToken($token), date('Y-m-d H:i:s', time() + 3600), date('Y-m-d H:i:s')]);
        $url = Config::url('/senha/redefinir') . '?token=' . urlencode($token);
        $this->mail->sendPasswordReset((string) $user['email'], (string) $user['name'], $url);
        $this->audit->record('auth.password_reset_requested', (int) $user['id'], 'user', (int) $user['id'], [], $request);
    }

    public function reset(string $token, string $password, string $confirmation, Request $request): array
    {
        $tokenHash = Security::hashToken($token);
        $statement = $this->pdo->prepare('SELECT pr.*, u.name, u.email, u.status FROM password_reset_tokens pr INNER JOIN users u ON u.id = pr.user_id WHERE pr.token_hash = ? AND pr.used_at IS NULL LIMIT 1');
        $statement->execute([$tokenHash]);
        $row = $statement->fetch();
        if (!$row || strtotime((string) $row['expires_at']) < time() || $row['status'] !== 'active') {
            return ['ok' => false, 'message' => 'Este link de recuperacao e invalido ou expirou.'];
        }
        $errors = PasswordPolicy::validate($password, $confirmation);
        if ($errors !== []) {
            return ['ok' => false, 'message' => implode(' ', $errors)];
        }
        $this->pdo->beginTransaction();
        try {
            $this->users->updatePassword((int) $row['user_id'], password_hash($password, PASSWORD_DEFAULT));
            $used = $this->pdo->prepare('UPDATE password_reset_tokens SET used_at = ? WHERE id = ? AND used_at IS NULL');
            $used->execute([date('Y-m-d H:i:s'), (int) $row['id']]);
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
        $this->audit->record('auth.password_reset_completed', (int) $row['user_id'], 'user', (int) $row['user_id'], [], $request);
        return ['ok' => true];
    }
}
