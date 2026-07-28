<?php
declare(strict_types=1);
namespace App\Support;

use PDO;

final class Repository {
    public function __construct(private PDO $db) {}
    public function all(string $table, ?string $where = null, array $params=[]): array { $sql="SELECT * FROM `$table` WHERE deleted_at IS NULL".($where ? " AND $where" : '').' ORDER BY id DESC'; $s=$this->db->prepare($sql); $s->execute($params); return $s->fetchAll(); }
    public function paginate(string $table, array $searchFields, string $query, int $page, int $perPage=20): array { $page=max(1,$page); $perPage=min(100,max(5,$perPage)); $where='deleted_at IS NULL'; $params=[]; if($query!=='' && $searchFields!==[]){$clauses=[];foreach($searchFields as $field){$clauses[]="CAST(`$field` AS CHAR) LIKE ?";$params[]='%'.$query.'%';}$where.=' AND ('.implode(' OR ',$clauses).')';} $count=$this->db->prepare("SELECT COUNT(*) FROM `$table` WHERE $where");$count->execute($params);$total=(int)$count->fetchColumn();$sql="SELECT * FROM `$table` WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET ".(($page-1)*$perPage);$rows=$this->db->prepare($sql);$rows->execute($params);return ['rows'=>$rows->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>max(1,(int)ceil($total/$perPage))]; }
    public function find(string $table, int $id): ?array { $s=$this->db->prepare("SELECT * FROM `$table` WHERE id=? AND deleted_at IS NULL"); $s->execute([$id]); return $s->fetch() ?: null; }
    public function findBy(string $table, string $column, string $value): ?array { $s=$this->db->prepare("SELECT * FROM `$table` WHERE `$column`=? AND deleted_at IS NULL LIMIT 1"); $s->execute([$value]); return $s->fetch() ?: null; }
    public function count(string $table): int { return (int) $this->db->query("SELECT COUNT(*) FROM `$table` WHERE deleted_at IS NULL")->fetchColumn(); }
    public function save(string $table, array $data, ?int $id=null): int { if ($id) { $sets=[]; foreach ($data as $k=>$v) $sets[]="`$k`=?"; $s=$this->db->prepare("UPDATE `$table` SET ".implode(',',$sets).",updated_at=NOW() WHERE id=?"); $s->execute([...array_values($data),$id]); return $id; } $data['created_at'] ??= date('Y-m-d H:i:s'); $data['updated_at'] ??= date('Y-m-d H:i:s'); $cols=array_keys($data); $s=$this->db->prepare("INSERT INTO `$table` (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")"); $s->execute(array_values($data)); return (int)$this->db->lastInsertId(); }
    public function softDelete(string $table, int $id): void { $s=$this->db->prepare("UPDATE `$table` SET deleted_at=NOW(),updated_at=NOW() WHERE id=?"); $s->execute([$id]); }
}
