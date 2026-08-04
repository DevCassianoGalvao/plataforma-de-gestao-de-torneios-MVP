<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MatchMediaRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(int $matchId): array
    {
        $s = $this->pdo->prepare('SELECT mm.*, u.name AS uploader_name, r.name AS reviewer_name, ci.name AS checklist_name, ci.expected_moment FROM match_media mm INNER JOIN users u ON u.id=mm.uploaded_by LEFT JOIN users r ON r.id=mm.reviewed_by LEFT JOIN championship_evidence_checklist_items ci ON ci.id=mm.checklist_item_id WHERE mm.match_id=? AND mm.deleted_at IS NULL ORDER BY ci.display_order, mm.created_at DESC, mm.id DESC');
        $s->execute([$matchId]); return $s->fetchAll();
    }
    public function find(int $id): ?array
    { $s=$this->pdo->prepare('SELECT * FROM match_media WHERE id=? AND deleted_at IS NULL LIMIT 1'); $s->execute([$id]); return $s->fetch() ?: null; }
    public function checklist(int $championshipId, int $matchId): array
    {
        $s=$this->pdo->prepare("SELECT ci.*, COUNT(mm.id) AS total_files, SUM(mm.review_status='approved') AS approved_files, SUM(mm.review_status='submitted') AS submitted_files, SUM(mm.review_status='rejected') AS rejected_files, SUM(mm.caption IS NOT NULL AND TRIM(mm.caption) <> '') AS files_with_notes FROM championship_evidence_checklist_items ci LEFT JOIN match_media mm ON mm.checklist_item_id=ci.id AND mm.match_id=? AND mm.deleted_at IS NULL WHERE ci.championship_id=? AND ci.deleted_at IS NULL AND ci.is_active=1 GROUP BY ci.id ORDER BY ci.display_order,ci.id");
        $s->execute([$matchId,$championshipId]); return $s->fetchAll();
    }
    public function missing(int $championshipId, int $matchId, string $gate): array
    {
        $column=['start'=>'blocks_operation_start','approval'=>'blocks_approval_submission','document'=>'blocks_document_completion'][$gate] ?? 'blocks_approval_submission';
        $s=$this->pdo->prepare("SELECT ci.* FROM championship_evidence_checklist_items ci LEFT JOIN match_media mm ON mm.checklist_item_id=ci.id AND mm.match_id=? AND mm.deleted_at IS NULL AND mm.review_status='approved' LEFT JOIN match_evidence_exceptions ex ON ex.match_id=? AND ex.checklist_item_id=ci.id AND ex.exception_type=? WHERE ci.championship_id=? AND ci.deleted_at IS NULL AND ci.is_active=1 AND ci.is_required=1 AND ci.$column=1 GROUP BY ci.id HAVING COUNT(mm.id) < ci.min_files AND COUNT(ex.id)=0 ORDER BY ci.display_order,ci.id");
        $s->execute([$matchId,$matchId,$gate,$championshipId]); return $s->fetchAll();
    }
    public function itemForMatch(int $itemId, int $championshipId): ?array
    { $s=$this->pdo->prepare('SELECT * FROM championship_evidence_checklist_items WHERE id=? AND championship_id=? AND deleted_at IS NULL AND is_active=1'); $s->execute([$itemId,$championshipId]); return $s->fetch() ?: null; }
    public function countForItem(int $matchId, int $itemId): int
    { $s=$this->pdo->prepare('SELECT COUNT(*) FROM match_media WHERE match_id=? AND checklist_item_id=? AND deleted_at IS NULL'); $s->execute([$matchId,$itemId]); return (int)$s->fetchColumn(); }
    public function create(array $data): int
    {
        $now=date('Y-m-d H:i:s');
        $s=$this->pdo->prepare("INSERT INTO match_media (match_id,championship_id,checklist_item_id,title,caption,storage_path,original_name,mime_type,file_hash,visibility,status,review_status,captured_at,uploaded_by,supersedes_media_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,'approved',?,?,?,?,?,?)");
        $s->execute([$data['match_id'],$data['championship_id'],$data['checklist_item_id'],$data['title'],$data['caption'],$data['storage_path'],$data['original_name'],$data['mime_type'],$data['file_hash'],$data['visibility'],$data['review_status'],$data['captured_at'],$data['uploaded_by'],$data['supersedes_media_id'],$now,$now]);
        return (int)$this->pdo->lastInsertId();
    }
    public function review(int $id, int $userId, string $status, ?string $reason): bool
    { $s=$this->pdo->prepare('UPDATE match_media SET review_status=?, reviewed_by=?, reviewed_at=?, rejection_reason=?, updated_at=? WHERE id=? AND deleted_at IS NULL'); $now=date('Y-m-d H:i:s'); return $s->execute([$status,$userId,$now,$reason,$now,$id]); }
    public function updateNotes(int $id, string $caption): bool
    { $s=$this->pdo->prepare('UPDATE match_media SET caption=?, updated_at=? WHERE id=? AND deleted_at IS NULL'); return $s->execute([$caption ?: null,date('Y-m-d H:i:s'),$id]); }
    public function remove(int $id, int $userId, string $reason): bool
    { $s=$this->pdo->prepare('UPDATE match_media SET deleted_at=?, removed_by=?, removed_reason=?, updated_at=? WHERE id=? AND deleted_at IS NULL'); $now=date('Y-m-d H:i:s'); return $s->execute([$now,$userId,$reason ?: null,$now,$id]); }
    public function replace(int $oldId, int $newId): void
    { $s=$this->pdo->prepare('UPDATE match_media SET replaced_by_media_id=?, updated_at=? WHERE id=?'); $s->execute([$newId,date('Y-m-d H:i:s'),$oldId]); }
    public function history(int $matchId): array
    { $s=$this->pdo->prepare('SELECT h.*,u.name AS user_name,ci.name AS checklist_name FROM match_evidence_history h INNER JOIN users u ON u.id=h.created_by LEFT JOIN championship_evidence_checklist_items ci ON ci.id=h.checklist_item_id WHERE h.match_id=? ORDER BY h.created_at DESC,h.id DESC');$s->execute([$matchId]);return $s->fetchAll(); }
    public function log(int $matchId, ?int $mediaId, ?int $itemId, string $action, string $details, int $userId): void
    { $s=$this->pdo->prepare('INSERT INTO match_evidence_history (match_media_id,match_id,checklist_item_id,action,details,created_by,created_at) VALUES (?,?,?,?,?,?,?)');$s->execute([$mediaId,$matchId,$itemId,$action,$details ?: null,$userId,date('Y-m-d H:i:s')]); }
    public function exception(int $matchId, ?int $itemId, string $type, string $reason, int $userId): void
    { $s=$this->pdo->prepare('INSERT INTO match_evidence_exceptions (match_id,checklist_item_id,exception_type,reason,created_by,created_at) VALUES (?,?,?,?,?,?)');$s->execute([$matchId,$itemId,$type,$reason,$userId,date('Y-m-d H:i:s')]); }
}
