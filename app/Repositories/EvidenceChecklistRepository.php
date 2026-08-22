<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class EvidenceChecklistRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function championship(string $slug): ?array
    {
        $s = $this->pdo->prepare('SELECT * FROM championships WHERE slug = ? AND deleted_at IS NULL LIMIT 1');
        $s->execute([$slug]); return $s->fetch() ?: null;
    }
    public function championships(): array
    { return $this->pdo->query('SELECT id,name FROM championships WHERE deleted_at IS NULL ORDER BY name')->fetchAll(); }

    public function championshipById(int $id): ?array
    {
        $s = $this->pdo->prepare('SELECT id,name,slug FROM championships WHERE id = ? AND deleted_at IS NULL LIMIT 1');
        $s->execute([$id]); return $s->fetch() ?: null;
    }

    public function items(int $championshipId, bool $includeDeleted = false): array
    {
        $where = $includeDeleted ? '' : ' AND deleted_at IS NULL';
        $s = $this->pdo->prepare('SELECT i.*, (SELECT COUNT(*) FROM match_media mm WHERE mm.checklist_item_id = i.id AND mm.deleted_at IS NULL) AS usage_count FROM championship_evidence_checklist_items i WHERE championship_id = ?' . $where . ' ORDER BY deleted_at IS NOT NULL, display_order, id');
        $s->execute([$championshipId]); return $s->fetchAll();
    }

    public function item(int $id, int $championshipId, bool $includeDeleted = false): ?array
    {
        $s = $this->pdo->prepare('SELECT * FROM championship_evidence_checklist_items WHERE id = ? AND championship_id = ?' . ($includeDeleted ? '' : ' AND deleted_at IS NULL') . ' LIMIT 1');
        $s->execute([$id, $championshipId]); return $s->fetch() ?: null;
    }

    public function save(int $championshipId, array $data, int $userId, ?int $id = null): int
    {
        $now = date('Y-m-d H:i:s');
        $values = [$data['scope'] ?? 'match', $data['name'], $data['description'], $data['is_required'], $data['is_active'], $data['display_order'], $data['expected_moment'], $data['allowed_mime_types'], $data['min_files'], $data['max_files'], $data['max_file_size_bytes'], $data['notes_required'], $data['blocks_operation_start'], $data['blocks_approval_submission'], $data['blocks_document_completion'], $data['show_in_accountability']];
        if ($id !== null) {
            $s = $this->pdo->prepare('UPDATE championship_evidence_checklist_items SET scope=?, name=?, description=?, is_required=?, is_active=?, display_order=?, expected_moment=?, allowed_mime_types=?, min_files=?, max_files=?, max_file_size_bytes=?, notes_required=?, blocks_operation_start=?, blocks_approval_submission=?, blocks_document_completion=?, show_in_accountability=?, updated_at=? WHERE id=? AND championship_id=? AND deleted_at IS NULL');
            $s->execute([...$values, $now, $id, $championshipId]); return $id;
        }
        $s = $this->pdo->prepare('INSERT INTO championship_evidence_checklist_items (championship_id,scope,name,description,is_required,is_active,display_order,expected_moment,allowed_mime_types,min_files,max_files,max_file_size_bytes,notes_required,blocks_operation_start,blocks_approval_submission,blocks_document_completion,show_in_accountability,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $s->execute([$championshipId, ...$values, $userId, $now, $now]); return (int) $this->pdo->lastInsertId();
    }

    public function toggle(int $id, int $championshipId, bool $active): bool
    { $s = $this->pdo->prepare('UPDATE championship_evidence_checklist_items SET is_active=?, updated_at=? WHERE id=? AND championship_id=? AND deleted_at IS NULL'); return $s->execute([$active ? 1 : 0, date('Y-m-d H:i:s'), $id, $championshipId]); }
    public function delete(int $id, int $championshipId, int $userId): bool
    { $s = $this->pdo->prepare('UPDATE championship_evidence_checklist_items SET deleted_at=?, deleted_by=?, is_active=0, updated_at=? WHERE id=? AND championship_id=? AND deleted_at IS NULL'); $now=date('Y-m-d H:i:s'); return $s->execute([$now,$userId,$now,$id,$championshipId]); }
    public function restore(int $id, int $championshipId): bool
    { $s = $this->pdo->prepare('UPDATE championship_evidence_checklist_items SET deleted_at=NULL, deleted_by=NULL, updated_at=? WHERE id=? AND championship_id=? AND deleted_at IS NOT NULL'); return $s->execute([date('Y-m-d H:i:s'),$id,$championshipId]); }
    public function reorder(int $championshipId, array $ids): void
    { $s=$this->pdo->prepare('UPDATE championship_evidence_checklist_items SET display_order=?, updated_at=? WHERE id=? AND championship_id=? AND deleted_at IS NULL'); foreach (array_values(array_unique(array_map('intval',$ids))) as $order=>$id) $s->execute([$order+1,date('Y-m-d H:i:s'),$id,$championshipId]); }
    public function duplicate(int $fromChampionshipId, int $toChampionshipId, int $userId): ?int
    {
        if ($fromChampionshipId === $toChampionshipId || $this->activeCount($toChampionshipId) > 0) return null;
        $items = $this->items($fromChampionshipId);
        $this->pdo->beginTransaction();
        try {
            $count = 0;
            foreach ($items as $item) {
                $copy = $item;
                unset($copy['id'], $copy['usage_count'], $copy['deleted_at'], $copy['deleted_by']);
                $this->save($toChampionshipId, $copy, $userId);
                $count++;
            }
            $this->pdo->commit(); return $count;
        } catch (\Throwable $e) { $this->pdo->rollBack(); throw $e; }
    }

    public function activeCount(int $championshipId): int
    {
        $s = $this->pdo->prepare('SELECT COUNT(*) FROM championship_evidence_checklist_items WHERE championship_id = ? AND deleted_at IS NULL');
        $s->execute([$championshipId]); return (int) $s->fetchColumn();
    }
}
