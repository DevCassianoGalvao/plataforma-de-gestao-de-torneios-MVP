<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$id]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([strtolower(trim($email))]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function list(string $search = ''): array
    {
        $search = trim($search);
        if ($search === '') {
            return $this->pdo->query('SELECT * FROM users WHERE deleted_at IS NULL ORDER BY name, id')->fetchAll();
        }
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE deleted_at IS NULL AND (name LIKE ? OR email LIKE ?) ORDER BY name, id');
        $term = '%' . $search . '%';
        $statement->execute([$term, $term]);
        return $statement->fetchAll();
    }

    public function listByRole(string $roleKey): array
    {
        $statement = $this->pdo->prepare('SELECT DISTINCT u.* FROM users u INNER JOIN user_roles ur ON ur.user_id = u.id INNER JOIN roles r ON r.id = ur.role_id WHERE r.`key` = ? AND u.status = \'active\' AND u.deleted_at IS NULL ORDER BY u.name');
        $statement->execute([$roleKey]);
        return $statement->fetchAll();
    }

    public function create(string $name, string $email, string $passwordHash, string $status = 'active'): int
    {
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute([$name, strtolower(trim($email)), $passwordHash, $status, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $email): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET name = ?, email = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$name, strtolower(trim($email)), date('Y-m-d H:i:s'), $id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET status = ?, locked_until = ?, failed_login_attempts = 0, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $lockedUntil = $status === 'blocked' ? date('Y-m-d H:i:s', time() + 900) : null;
        $statement->execute([$status, $lockedUntil, date('Y-m-d H:i:s'), $id]);
    }

    public function softDelete(int $id): bool
    {
        $now = date('Y-m-d H:i:s');
        $this->pdo->beginTransaction();
        try {
            // Keep the user row for historical foreign keys, but remove active access
            // and free the original e-mail for a future account.
            $user = $this->pdo->prepare('SELECT email FROM users WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $user->execute([$id]);
            $email = $user->fetchColumn();
            if ($email === false) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]);
            $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$id]);
            $statement = $this->pdo->prepare('UPDATE users SET email = ?, deleted_at = ?, status = \'inactive\', locked_until = NULL, failed_login_attempts = 0, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
            $tombstoneEmail = '__deleted_' . $id . '_' . substr(hash('sha256', (string) $email), 0, 24) . '@invalid.local';
            $statement->execute([$tombstoneEmail, $now, $now, $id]);
            $changed = $statement->rowCount() === 1;
            if ($changed) {
                $this->pdo->commit();
            } else {
                $this->pdo->rollBack();
            }
            return $changed;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function hasAnotherActiveAdministrator(int $excludedUserId): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users u INNER JOIN user_roles ur ON ur.user_id = u.id INNER JOIN roles r ON r.id = ur.role_id WHERE u.status = \'active\' AND u.deleted_at IS NULL AND r.`key` = \'administrator\' AND u.id <> ?');
        $statement->execute([$excludedUserId]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$passwordHash, date('Y-m-d H:i:s'), $id]);
    }

    public function clearFailedLoginAttempts(string $emailHash, string $ip): void
    {
        $statement = $this->pdo->prepare('DELETE FROM login_attempts WHERE successful = 0 AND email_hash = ?');
        $statement->execute([$emailHash]);
    }

    public function updateAvatar(int $id, string $path): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET avatar_path = ?, updated_at = ? WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$path, date('Y-m-d H:i:s'), $id]);
    }

    public function markSuccessfulLogin(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET last_login_at = ?, failed_login_attempts = 0, locked_until = NULL, updated_at = ? WHERE id = ?');
        $now = date('Y-m-d H:i:s');
        $statement->execute([$now, $now, $id]);
    }

    public function markFailedLogin(int $id, int $attempts, ?string $lockedUntil): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET failed_login_attempts = ?, locked_until = ?, updated_at = ? WHERE id = ?');
        $statement->execute([$attempts, $lockedUntil, date('Y-m-d H:i:s'), $id]);
    }

    public function roles(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT r.* FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ? ORDER BY r.name');
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function permissions(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT DISTINCT p.`key` FROM permissions p INNER JOIN role_permissions rp ON rp.permission_id = p.id INNER JOIN user_roles ur ON ur.role_id = rp.role_id WHERE ur.user_id = ? ORDER BY p.`key`');
        $statement->execute([$userId]);
        return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function rolesCatalog(): array
    {
        return $this->pdo->query('SELECT * FROM roles ORDER BY name')->fetchAll();
    }

    public function roleIds(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT role_id FROM user_roles WHERE user_id = ?');
        $statement->execute([$userId]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function syncRoles(int $userId, array $roleIds, ?int $createdBy): void
    {
        $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM user_roles WHERE user_id = ?');
            $delete->execute([$userId]);
            $insert = $this->pdo->prepare('INSERT INTO user_roles (user_id, role_id, created_at, created_by) VALUES (?, ?, ?, ?)');
            foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
                $insert->execute([$userId, $roleId, date('Y-m-d H:i:s'), $createdBy]);
            }
            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function findRole(int $roleId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM roles WHERE id = ?');
        $statement->execute([$roleId]);
        $role = $statement->fetch();
        return $role ?: null;
    }
}
