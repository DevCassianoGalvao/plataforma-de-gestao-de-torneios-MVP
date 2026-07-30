<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class GovernanceRepository
{
    public function __construct(private readonly PDO $pdo) {}
    public function organizations(): array { return $this->pdo->query('SELECT * FROM organizations WHERE deleted_at IS NULL ORDER BY name')->fetchAll(); }
    public function projects(): array { return $this->pdo->query('SELECT p.*, o.name AS organization_name FROM projects p INNER JOIN organizations o ON o.id = p.organization_id WHERE p.deleted_at IS NULL ORDER BY o.name, p.name')->fetchAll(); }
    public function createOrganization(string $name, string $slug, int $userId): void { $now = date('Y-m-d H:i:s'); $s = $this->pdo->prepare("INSERT INTO organizations (name, slug, status, created_by, created_at, updated_at) VALUES (?, ?, 'active', ?, ?, ?)"); $s->execute([$name, $slug, $userId, $now, $now]); }
    public function createProject(int $organizationId, string $name, string $slug, int $userId): void { $now = date('Y-m-d H:i:s'); $s = $this->pdo->prepare("INSERT INTO projects (organization_id, name, slug, status, created_by, created_at, updated_at) VALUES (?, ?, ?, 'active', ?, ?, ?)"); $s->execute([$organizationId, $name, $slug, $userId, $now, $now]); }
}
