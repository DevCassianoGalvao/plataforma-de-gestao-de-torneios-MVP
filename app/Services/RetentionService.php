<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\RetentionRepository;

final class RetentionService
{
    private const ENTITIES = [
        'campeonatos' => ['table' => 'championships', 'scope' => 'sports_history'],
        'equipes' => ['table' => 'teams', 'scope' => 'sports_history'],
        'atletas' => ['table' => 'athletes', 'scope' => 'sports_history'],
        'noticias' => ['table' => 'news_articles', 'scope' => 'operational_drafts'],
        'simulacoes' => ['table' => 'simulation_scenarios', 'scope' => 'operational_drafts'],
    ];

    public function __construct(private readonly \PDO $pdo, private readonly RetentionRepository $repository, private readonly AuditService $audit) {}

    public function archive(string $entity, int $id, int $userId, string $reason): array
    {
        $definition = $this->definition($entity);
        $policy = $this->repository->policy($definition['scope']);
        if (!$policy || !(int) $policy['allow_archive']) return $this->fail('O arquivamento não está permitido para este tipo de dado.');
        $record = $this->record($definition['table'], $id);
        if (!$record) return $this->fail('Registro não encontrado.');
        $reason = trim($reason);
        if ($reason === '') return $this->fail('Informe o motivo do arquivamento.');
        $now = date('Y-m-d H:i:s');
        $statement = $this->pdo->prepare('UPDATE ' . $definition['table'] . " SET status = 'archived', deleted_at = ? WHERE id = ? AND deleted_at IS NULL");
        $statement->execute([$now, $id]);
        if ($statement->rowCount() === 0) return $this->fail('O registro já está arquivado.');
        $this->repository->log($definition['scope'], $entity, $id, 'archive', (string) ($record['status'] ?? null), 'archived', $reason, $userId, ['table' => $definition['table']]);
        $this->audit->record('retention.archived', $userId, $entity, $id, ['reason' => $reason], null);
        return ['ok' => true, 'errors' => []];
    }

    public function restore(string $entity, int $id, int $userId, string $reason): array
    {
        $definition = $this->definition($entity);
        $policy = $this->repository->policy($definition['scope']);
        if (!$policy || !(int) $policy['allow_restore']) return $this->fail('A restauração não está permitida para este tipo de dado.');
        $record = $this->record($definition['table'], $id);
        if (!$record) return $this->fail('Registro não encontrado.');
        $archive = $this->repository->lastArchive($entity, $id);
        $status = trim((string) ($archive['previous_status'] ?? 'active')) ?: 'active';
        $reason = trim($reason);
        if ($reason === '') return $this->fail('Informe o motivo da restauração.');
        $statement = $this->pdo->prepare('UPDATE ' . $definition['table'] . ' SET status = ?, deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL');
        $statement->execute([$status, $id]);
        if ($statement->rowCount() === 0) return $this->fail('O registro não está arquivado.');
        $this->repository->log($definition['scope'], $entity, $id, 'restore', 'archived', $status, $reason, $userId);
        $this->audit->record('retention.restored', $userId, $entity, $id, ['reason' => $reason], null);
        return ['ok' => true, 'errors' => []];
    }

    private function definition(string $entity): array
    {
        if (!isset(self::ENTITIES[$entity])) throw new \InvalidArgumentException('Tipo de registro não pode ser administrado por retenção.');
        return self::ENTITIES[$entity];
    }

    private function record(string $table, int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, status, deleted_at FROM ' . $table . ' WHERE id = ? LIMIT 1');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    private function fail(string $message): array { return ['ok' => false, 'errors' => [$message]]; }
}
