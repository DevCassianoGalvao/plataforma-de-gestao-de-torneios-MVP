<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\RetentionRepository;
use PDO;

/**
 * Permanently removes only sports-domain records selected by an administrator.
 * The dependency graph is read from MySQL metadata so new domain tables are not
 * silently left behind when they reference a championship, team or athlete.
 */
final class PermanentDeletionService
{
    public const CONFIRMATION = 'EXCLUIR DEFINITIVAMENTE';

    private const MAX_SELECTION = 500;
    private const MAX_DEPENDENT_ROWS = 100000;

    private const DEFINITIONS = [
        'campeonatos' => ['table' => 'championships', 'label' => 'Campeonatos', 'name_column' => 'name', 'scope' => 'sports_history'],
        'equipes' => ['table' => 'teams', 'label' => 'Equipes', 'name_column' => 'name', 'scope' => 'sports_history'],
        'atletas' => ['table' => 'athletes', 'label' => 'Atletas', 'name_column' => 'full_name', 'scope' => 'sports_history'],
    ];

    private const PROTECTED_TABLES = [
        'users', 'roles', 'permissions', 'role_permissions', 'user_roles',
        'password_reset_tokens', 'login_attempts', 'audit_logs',
        'admin_notifications', 'admin_notification_reads',
        'schema_migrations', 'retention_policies', 'retention_actions',
        'application_backups', 'application_backup_settings',
    ];

    private const FILE_COLUMNS = [
        'storage_path', 'photo_path', 'shield_path', 'logo_path', 'logo_light_path',
        'logo_dark_path', 'banner_path', 'favicon_path', 'social_image_path',
        'cover_image_path', 'signed_storage_path',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly RetentionRepository $retention,
        private readonly AuditService $audit,
        private readonly StorageService $storage,
    ) {
    }

    public function definitions(): array
    {
        return self::DEFINITIONS;
    }

    public function availableRecords(): array
    {
        $result = [];
        foreach (self::DEFINITIONS as $entity => $definition) {
            $table = $this->identifier($definition['table']);
            $column = $this->identifier($definition['name_column']);
            $display = $entity === 'atletas'
                ? "COALESCE(NULLIF(sporting_name, ''), full_name)"
                : $column;
            $statement = $this->pdo->query("SELECT id, {$display} AS display_name, status, deleted_at FROM {$table} ORDER BY deleted_at IS NULL DESC, display_name ASC, id ASC");
            $records = [];
            foreach ($statement->fetchAll() as $record) {
                $records[] = [
                    'id' => (int) $record['id'],
                    'display_name' => (string) ($record['display_name'] ?? ('Registro #' . $record['id'])),
                    'status_label' => $this->statusLabel((string) ($record['status'] ?? '')),
                    'archived' => !empty($record['deleted_at']),
                ];
            }
            $result[$entity] = [
                'label' => $definition['label'],
                'records' => $records,
            ];
        }
        return $result;
    }

    public function preview(string $entity, array $ids): array
    {
        try {
            $definition = $this->definition($entity);
            $ids = $this->normalizeIds($ids);
            if ($ids === []) {
                return $this->fail('Selecione pelo menos um registro.');
            }
            if (count($ids) > self::MAX_SELECTION) {
                return $this->fail('Selecione no máximo ' . self::MAX_SELECTION . ' registros por operação.');
            }
            $policy = $this->retention->policy($definition['scope']);
            if (!$policy || !(int) $policy['allow_hard_delete'] || (int) $policy['protected']) {
                return $this->fail('A exclusão definitiva não está habilitada para este tipo de dado.');
            }
            $graph = $this->buildGraph($definition['table'], $ids);
            return [
                'ok' => true,
                'entity' => $entity,
                'ids' => $ids,
                'root_records' => count($graph['root_rows']),
                'total_rows' => count($graph['rows_flat']),
                'tables' => $this->tableCounts($graph['rows_flat']),
            ];
        } catch (\Throwable $error) {
            return $this->fail($error->getMessage());
        }
    }

    public function purge(string $entity, array $ids, int $userId, string $reason, string $confirmation): array
    {
        if (trim($confirmation) !== self::CONFIRMATION) {
            return $this->fail('Digite EXCLUIR DEFINITIVAMENTE para confirmar.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            return $this->fail('Informe o motivo da exclusão definitiva.');
        }
        if (mb_strlen($reason) > 1000) {
            return $this->fail('O motivo deve ter no máximo 1.000 caracteres.');
        }

        $definition = $this->definition($entity);
        $ids = $this->normalizeIds($ids);
        if ($ids === []) {
            return $this->fail('Selecione pelo menos um registro.');
        }
        if (count($ids) > self::MAX_SELECTION) {
            return $this->fail('Selecione no máximo ' . self::MAX_SELECTION . ' registros por operação.');
        }
        $policy = $this->retention->policy($definition['scope']);
        if (!$policy || !(int) $policy['allow_hard_delete'] || (int) $policy['protected']) {
            return $this->fail('A exclusão definitiva não está habilitada para este tipo de dado.');
        }

        $graph = $this->buildGraph($definition['table'], $ids);
        $deleted = 0;
        $guardianIds = $this->guardianIds($graph['rows_flat']);
        try {
            $this->pdo->beginTransaction();
            foreach ($graph['rows_flat'] as $row) {
                $deleted += $this->deleteRow($row['table'], $row['values'], $graph['primary_keys'][$row['table']]);
            }
            $deleted += $this->deleteOrphanGuardians($guardianIds);
            foreach ($graph['root_rows'] as $root) {
                $this->retention->log($definition['scope'], $entity, (int) $root['id'], 'purge', $root['status'] ?? null, null, $reason, $userId, [
                    'deleted_rows' => $deleted,
                    'selection_count' => count($ids),
                ]);
            }
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        foreach ($graph['files'] as $path) {
            $this->storage->delete($path);
        }
        try {
            $this->audit->record('retention.purged', $userId, $entity, implode(',', $ids), [
                'selection_count' => count($ids),
                'deleted_rows' => $deleted,
                'reason' => $reason,
            ]);
        } catch (\Throwable) {
            // retention_actions already provides a durable record if notifications are unavailable.
        }

        return ['ok' => true, 'deleted_rows' => $deleted, 'selection_count' => count($ids), 'errors' => []];
    }

    private function definition(string $entity): array
    {
        if (!isset(self::DEFINITIONS[$entity])) {
            throw new \InvalidArgumentException('Tipo de registro não pode ser excluído nesta tela.');
        }
        return self::DEFINITIONS[$entity];
    }

    private function buildGraph(string $rootTable, array $ids): array
    {
        $schema = $this->schemaMetadata();
        $rootRows = $this->fetchRowsByColumn($rootTable, 'id', $ids);
        if (count($rootRows) !== count($ids)) {
            throw new \RuntimeException('Um ou mais registros selecionados não foram encontrados.');
        }

        $rows = [];
        $pending = [$rootTable];
        $depths = [];
        foreach ($rootRows as $row) {
            $this->addGraphRow($rows, $depths, $schema['primary_keys'], $rootTable, $row, 0);
        }

        while ($pending !== []) {
            $parentTable = array_shift($pending);
            $parentRows = array_values($rows[$parentTable] ?? []);
            if ($parentRows === []) {
                continue;
            }
            $parentDepth = 0;
            foreach ($parentRows as $parentRow) {
                $parentDepth = max($parentDepth, (int) ($parentRow['depth'] ?? 0));
            }
            foreach ($schema['relations'][$parentTable] ?? [] as $relation) {
                $childTable = $relation['child_table'];
                if (in_array($childTable, self::PROTECTED_TABLES, true)) {
                    throw new \RuntimeException('A exclusão foi bloqueada porque o registro possui vínculo com uma tabela protegida: ' . $childTable . '.');
                }
                if (count($relation['parent_columns']) !== 1 || count($relation['child_columns']) !== 1) {
                    throw new \RuntimeException('A exclusão foi bloqueada por uma chave estrangeira composta em ' . $childTable . '.');
                }
                $parentColumn = $relation['parent_columns'][0];
                $childColumn = $relation['child_columns'][0];
                $values = [];
                foreach ($parentRows as $parent) {
                    if (array_key_exists($parentColumn, $parent['values']) && $parent['values'][$parentColumn] !== null) {
                        $values[(string) $parent['values'][$parentColumn]] = $parent['values'][$parentColumn];
                    }
                }
                if ($values === []) {
                    continue;
                }
                foreach (array_chunk(array_values($values), 500) as $chunk) {
                    foreach ($this->fetchRowsByColumn($childTable, $childColumn, $chunk) as $childRow) {
                        $childDepth = $parentDepth + 1;
                        if ($this->addGraphRow($rows, $depths, $schema['primary_keys'], $childTable, $childRow, $childDepth)) {
                            $pending[] = $childTable;
                        }
                    }
                }
            }
        }

        $flat = [];
        foreach ($rows as $tableRows) {
            foreach ($tableRows as $row) {
                $flat[] = $row;
            }
        }
        if (count($flat) > self::MAX_DEPENDENT_ROWS) {
            throw new \RuntimeException('A exclusão foi bloqueada porque a seleção possui dependências demais para uma única operação.');
        }
        usort($flat, static fn (array $left, array $right): int => $right['depth'] <=> $left['depth']);
        return [
            'root_rows' => $rootRows,
            'rows_flat' => $flat,
            'primary_keys' => $schema['primary_keys'],
            'files' => $this->collectFiles($flat),
        ];
    }

    private function schemaMetadata(): array
    {
        $foreignKeys = $this->pdo->query("SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME, ORDINAL_POSITION FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION")->fetchAll();
        $grouped = [];
        foreach ($foreignKeys as $foreignKey) {
            $key = $foreignKey['TABLE_NAME'] . '|' . $foreignKey['CONSTRAINT_NAME'];
            $grouped[$key] ??= [
                'child_table' => (string) $foreignKey['TABLE_NAME'],
                'parent_table' => (string) $foreignKey['REFERENCED_TABLE_NAME'],
                'child_columns' => [],
                'parent_columns' => [],
            ];
            $grouped[$key]['child_columns'][] = (string) $foreignKey['COLUMN_NAME'];
            $grouped[$key]['parent_columns'][] = (string) $foreignKey['REFERENCED_COLUMN_NAME'];
        }
        $relations = [];
        foreach ($grouped as $relation) {
            $relations[$relation['parent_table']][] = $relation;
        }

        $primaryRows = $this->pdo->query("SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY TABLE_NAME, ORDINAL_POSITION")->fetchAll();
        $primaryKeys = [];
        foreach ($primaryRows as $primaryRow) {
            $primaryKeys[$primaryRow['TABLE_NAME']][] = (string) $primaryRow['COLUMN_NAME'];
        }
        return ['relations' => $relations, 'primary_keys' => $primaryKeys];
    }

    private function fetchRowsByColumn(string $table, string $column, array $values): array
    {
        $values = array_values(array_unique(array_map('strval', $values)));
        if ($values === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $statement = $this->pdo->prepare('SELECT * FROM ' . $this->identifier($table) . ' WHERE ' . $this->identifier($column) . ' IN (' . $placeholders . ')');
        $statement->execute($values);
        return $statement->fetchAll();
    }

    private function addGraphRow(array &$rows, array &$depths, array $primaryKeys, string $table, array $values, int $depth): bool
    {
        $keys = $primaryKeys[$table] ?? [];
        if ($keys === []) {
            throw new \RuntimeException('A exclusão foi bloqueada porque a tabela ' . $table . ' não possui chave primária.');
        }
        $signatureParts = [];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $values)) {
                throw new \RuntimeException('A exclusão foi bloqueada porque a chave de ' . $table . ' está incompleta.');
            }
            $signatureParts[] = (string) $values[$key];
        }
        $signature = $table . ':' . implode('|', $signatureParts);
        if (isset($depths[$signature]) && $depth <= $depths[$signature]) {
            return false;
        }
        $depths[$signature] = $depth;
        $rows[$table][$signature] = ['table' => $table, 'values' => $values, 'depth' => $depth];
        return true;
    }

    private function deleteRow(string $table, array $values, array $primaryKeys): int
    {
        if (in_array($table, self::PROTECTED_TABLES, true)) {
            throw new \RuntimeException('Tentativa de excluir tabela protegida: ' . $table . '.');
        }
        $where = [];
        $parameters = [];
        foreach ($primaryKeys as $key) {
            $where[] = $this->identifier($key) . ' = ?';
            $parameters[] = $values[$key];
        }
        $statement = $this->pdo->prepare('DELETE FROM ' . $this->identifier($table) . ' WHERE ' . implode(' AND ', $where));
        $statement->execute($parameters);
        return $statement->rowCount();
    }

    private function deleteOrphanGuardians(array $guardianIds): int
    {
        $deleted = 0;
        foreach (array_chunk($guardianIds, 500) as $chunk) {
            if ($chunk === []) {
                continue;
            }
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $statement = $this->pdo->prepare('DELETE g FROM legal_guardians g LEFT JOIN athlete_guardians ag ON ag.guardian_id = g.id WHERE g.id IN (' . $placeholders . ') AND ag.guardian_id IS NULL');
            $statement->execute($chunk);
            $deleted += $statement->rowCount();
        }
        return $deleted;
    }

    private function guardianIds(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            if ($row['table'] === 'athlete_guardians' && !empty($row['values']['guardian_id'])) {
                $ids[(string) $row['values']['guardian_id']] = (int) $row['values']['guardian_id'];
            }
        }
        return array_values($ids);
    }

    private function collectFiles(array $rows): array
    {
        $files = [];
        foreach ($rows as $row) {
            foreach ($row['values'] as $column => $value) {
                if (!is_string($value) || trim($value) === '') {
                    continue;
                }
                if (in_array($column, self::FILE_COLUMNS, true) || str_ends_with((string) $column, '_path')) {
                    $files[$value] = $value;
                }
            }
        }
        return array_values($files);
    }

    private function tableCounts(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['table']] = ($counts[$row['table']] ?? 0) + 1;
        }
        arsort($counts);
        return $counts;
    }

    private function normalizeIds(array $ids): array
    {
        $normalized = [];
        foreach ($ids as $id) {
            $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false) {
                $normalized[(int) $id] = (int) $id;
            }
        }
        return array_values($normalized);
    }

    private function identifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new \RuntimeException('Identificador de banco inválido.');
        }
        return '`' . $identifier . '`';
    }

    private function statusLabel(string $status): string
    {
        return [
            'active' => 'Ativo', 'draft' => 'Rascunho', 'published' => 'Publicado',
            'archived' => 'Arquivado', 'inactive' => 'Inativo', 'blocked' => 'Bloqueado',
            'approved' => 'Aprovado', 'finished' => 'Encerrado',
        ][$status] ?? ($status !== '' ? ucfirst(str_replace('_', ' ', $status)) : 'Sem status');
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'errors' => [$message]];
    }
}
