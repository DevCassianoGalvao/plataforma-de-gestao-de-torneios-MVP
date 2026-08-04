<?php
declare(strict_types=1);
namespace App\Repositories;
use PDO;
final class BackupRepository {
    public function __construct(private readonly PDO $pdo) {}
    public function create(array $data): int { $now=date('Y-m-d H:i:s'); $s=$this->pdo->prepare('INSERT INTO application_backups (backup_key,type,status,local_status,validation_status,remote_status,remote_provider,started_at,created_by,created_at,updated_at) VALUES (?,?,\'running\',\'pending\',\'pending\',\'pending\',?,?,?,?,?)'); $s->execute([$data['backup_key'],$data['type'],$data['provider']??null,$now,$data['user_id']??null,$now,$now]); return (int)$this->pdo->lastInsertId(); }
    public function update(int $id,array $data): void { $allowed=['status','local_status','validation_status','remote_status','local_path','remote_provider','remote_id','remote_path','size_bytes','sha256','duration_seconds','attempts','error_message','expires_at','completed_at']; $set=[];$values=[];foreach($allowed as $key)if(array_key_exists($key,$data)){$set[]="$key=?";$values[]=$data[$key];} $set[]='updated_at=?';$values[]=date('Y-m-d H:i:s');$values[]=$id;$this->pdo->prepare('UPDATE application_backups SET '.implode(',',$set).' WHERE id=?')->execute($values); }
    public function list(): array { return $this->pdo->query('SELECT * FROM application_backups WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 100')->fetchAll(); }
    public function find(int $id): ?array { $s=$this->pdo->prepare('SELECT * FROM application_backups WHERE id=? AND deleted_at IS NULL');$s->execute([$id]);return $s->fetch()?:null; }
    public function latestCompleted(): ?array { $s=$this->pdo->query("SELECT * FROM application_backups WHERE deleted_at IS NULL AND status='completed' ORDER BY completed_at DESC, id DESC LIMIT 1"); return $s->fetch() ?: null; }
    public function softDelete(int $id,int $userId): void { $this->pdo->prepare('UPDATE application_backups SET deleted_at=?,deleted_by=?,updated_at=? WHERE id=?')->execute([date('Y-m-d H:i:s'),$userId,date('Y-m-d H:i:s'),$id]); }
}
