<?php
declare(strict_types=1);
namespace App\Support;

use App\Services\ScopeService;
use RuntimeException;

/** Repository facade that hides out-of-scope records and validates ownership before writes. */
final class ScopedRepository
{
    private Repository $repository;
    private ScopeService $scopes;
    public function __construct(private \PDO $db, private array $user)
    { $this->repository=new Repository($db); $this->scopes=new ScopeService($db); }

    public function find(string $entity, int $id, string $permission='view'): ?array
    {
        $row=$this->repository->find($entity,$id); if (!$row) return null;
        return $this->allows($permission,$entity,$id) ? $row : null;
    }
    public function paginate(string $entity,array $fields,string $query,int $page,int $perPage=20): array
    {
        $all=$this->repository->all($entity); if ($query!=='') $all=array_values(array_filter($all,fn($row)=>$this->matches($row,$fields,$query)));
        $rows=array_values(array_filter($all,fn($row)=>$this->allows('view',$entity,(int)$row['id'])));
        $page=max(1,$page); $perPage=min(100,max(5,$perPage)); $total=count($rows);
        return ['rows'=>array_slice($rows,($page-1)*$perPage,$perPage),'total'=>$total,'page'=>$page,'pages'=>max(1,(int)ceil($total/$perPage))];
    }
    public function count(string $entity): int { return count(array_filter($this->repository->all($entity),fn($r)=>$this->allows('view',$entity,(int)$r['id']))); }
    public function save(string $entity,array $data,?int $id,string $permission): int
    {
        if ($id) { if (!$this->find($entity,$id,$permission)) throw new RuntimeException('Recurso não encontrado.'); return $this->repository->save($entity,$data,$id); }
        $context=$this->scopes->fromPayload($entity,$data);
        if ($entity==='organizations' && !$this->scopes->isSuperAdmin((int)$this->user['id'])) throw new RuntimeException('Acesso negado.');
        if ($entity!=='organizations' && !$this->scopes->allows((int)$this->user['id'],$permission,$context)) throw new RuntimeException('Acesso negado.');
        return $this->repository->save($entity,$data);
    }
    public function softDelete(string $entity,int $id): void { if (!$this->find($entity,$id,'delete')) throw new RuntimeException('Recurso não encontrado.'); $this->repository->softDelete($entity,$id); }
    private function allows(string $permission,string $entity,int $id): bool { return $this->scopes->allows((int)$this->user['id'],$permission,$this->scopes->context($entity,$id)); }
    private function matches(array $row,array $fields,string $query): bool { foreach($fields as $field) if(str_contains(mb_strtolower((string)($row[$field]??'')),mb_strtolower($query))) return true; return false; }
}
