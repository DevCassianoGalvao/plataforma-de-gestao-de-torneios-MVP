<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class EventDayRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(int $championshipId): array
    {
        $s = $this->pdo->prepare("SELECT ed.*, v.name AS venue_name, v.city AS venue_city, v.state AS venue_state, COUNT(em.id) AS media_count
            FROM event_days ed LEFT JOIN venues v ON v.id=ed.venue_id AND v.deleted_at IS NULL
            LEFT JOIN event_day_media em ON em.event_day_id=ed.id AND em.deleted_at IS NULL
            WHERE ed.championship_id=? AND ed.deleted_at IS NULL GROUP BY ed.id ORDER BY ed.event_date, ed.id");
        $s->execute([$championshipId]); return $s->fetchAll();
    }

    public function find(int $id, int $championshipId): ?array
    {
        $s=$this->pdo->prepare('SELECT ed.*,v.name AS venue_name,v.city AS venue_city,v.state AS venue_state FROM event_days ed LEFT JOIN venues v ON v.id=ed.venue_id WHERE ed.id=? AND ed.championship_id=? AND ed.deleted_at IS NULL LIMIT 1');
        $s->execute([$id,$championshipId]); return $s->fetch() ?: null;
    }

    public function create(array $data, int $userId): int
    {
        $now=date('Y-m-d H:i:s');
        $s=$this->pdo->prepare('INSERT INTO event_days (championship_id,venue_id,event_date,name,notes,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?)');
        $s->execute([(int)$data['championship_id'],$data['venue_id']?:null,$data['event_date'],$data['name']?:null,$data['notes']?:null,'active',$userId,$now,$now]);
        return (int)$this->pdo->lastInsertId();
    }

    public function delete(int $id, int $championshipId, int $userId): bool
    {
        $s=$this->pdo->prepare("UPDATE event_days SET status='archived',deleted_at=?,deleted_by=?,updated_at=? WHERE id=? AND championship_id=? AND deleted_at IS NULL");
        $now=date('Y-m-d H:i:s'); return $s->execute([$now,$userId,$now,$id,$championshipId]) && $s->rowCount()===1;
    }

    public function checklist(int $championshipId): array
    {
        $s=$this->pdo->prepare("SELECT * FROM championship_evidence_checklist_items WHERE championship_id=? AND scope='event_day' AND is_active=1 AND deleted_at IS NULL ORDER BY display_order,id");
        $s->execute([$championshipId]); return $s->fetchAll();
    }

    public function media(int $eventDayId): array
    {
        $s=$this->pdo->prepare('SELECT em.*,u.name AS uploader_name,r.name AS reviewer_name,ci.name AS checklist_name FROM event_day_media em INNER JOIN users u ON u.id=em.uploaded_by LEFT JOIN users r ON r.id=em.reviewed_by LEFT JOIN championship_evidence_checklist_items ci ON ci.id=em.checklist_item_id WHERE em.event_day_id=? AND em.deleted_at IS NULL ORDER BY em.created_at DESC,em.id DESC');
        $s->execute([$eventDayId]); return $s->fetchAll();
    }

    public function item(int $id, int $championshipId): ?array
    { $s=$this->pdo->prepare("SELECT * FROM championship_evidence_checklist_items WHERE id=? AND championship_id=? AND scope='event_day' AND is_active=1 AND deleted_at IS NULL LIMIT 1");$s->execute([$id,$championshipId]);return $s->fetch()?:null; }

    public function createMedia(array $data): int
    {
        $now=date('Y-m-d H:i:s');
        $s=$this->pdo->prepare("INSERT INTO event_day_media (event_day_id,championship_id,checklist_item_id,title,caption,storage_path,original_name,mime_type,file_hash,visibility,status,review_status,captured_at,uploaded_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,'private','approved',?,?,?,?,?)");
        $s->execute([$data['event_day_id'],$data['championship_id'],$data['checklist_item_id']?:null,$data['title'],$data['caption']?:null,$data['storage_path'],$data['original_name'],$data['mime_type'],$data['file_hash']?:null,$data['review_status'],$data['captured_at']?:null,$data['uploaded_by'],$now,$now]);
        return (int)$this->pdo->lastInsertId();
    }

    public function findMedia(int $id, int $eventDayId): ?array
    { $s=$this->pdo->prepare('SELECT * FROM event_day_media WHERE id=? AND event_day_id=? AND deleted_at IS NULL LIMIT 1');$s->execute([$id,$eventDayId]);return $s->fetch()?:null; }
    public function review(int $id,string $status,int $userId,?string $reason): bool
    { $s=$this->pdo->prepare('UPDATE event_day_media SET review_status=?,reviewed_by=?,reviewed_at=?,rejection_reason=?,updated_at=? WHERE id=? AND deleted_at IS NULL');$now=date('Y-m-d H:i:s');return $s->execute([$status,$userId,$now,$reason,$now,$id]); }
    public function removeMedia(int $id,int $userId,string $reason): bool
    { $s=$this->pdo->prepare('UPDATE event_day_media SET deleted_at=?,removed_by=?,removed_reason=?,updated_at=? WHERE id=? AND deleted_at IS NULL');$now=date('Y-m-d H:i:s');return $s->execute([$now,$userId,$reason?:null,$now,$id]); }
}
