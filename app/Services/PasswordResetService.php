<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use RuntimeException;

final class PasswordResetService
{
    public function __construct(private PDO $db) {}

    public function request(string $email): ?string
    {
        $user = $this->db->prepare('SELECT id FROM users WHERE email=? AND status="active" AND deleted_at IS NULL');
        $user->execute([$email]);
        $row = $user->fetch();
        if (!$row) return null;
        $token = bin2hex(random_bytes(32));
        $this->db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$row['id']]);
        $this->db->prepare('INSERT INTO password_reset_tokens(user_id,token_hash,expires_at,created_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 30 MINUTE),NOW())')->execute([$row['id'],hash('sha256',$token)]);
        return $token;
    }

    public function reset(string $token, string $password): int
    {
        if (strlen($password) < 12) throw new RuntimeException('A senha deve ter pelo menos 12 caracteres.');
        $find = $this->db->prepare('SELECT * FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL AND expires_at>NOW() LIMIT 1');
        $find->execute([hash('sha256',$token)]);
        $reset = $find->fetch();
        if (!$reset) throw new RuntimeException('Link inválido ou expirado.');
        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$reset['user_id']]);
            $this->db->prepare('UPDATE password_reset_tokens SET used_at=NOW() WHERE id=?')->execute([$reset['id']]);
            $this->db->commit();
            return (int)$reset['user_id'];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
