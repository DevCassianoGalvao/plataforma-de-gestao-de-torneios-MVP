<?php
declare(strict_types=1);
namespace App\Policies;

use App\Services\AuditService;
use App\Services\ScopeService;
use App\Support\Database;
use App\Support\Security;
use App\Support\Session;

final class AuthPolicy {
    public static function requireUser(): array { $u=Session::user(); if (!$u) Security::redirect('/login'); return $u; }
    public static function requireSuperAdmin(): array { $u=self::requireUser(); if (!(new ScopeService(Database::connection()))->isSuperAdmin((int)$u['id'])) { http_response_code(403); exit('Acesso negado.'); } return $u; }
    public static function requirePermission(string $permission,string $entity,int $id): array {
        $u=self::requireUser(); $scopes=new ScopeService(Database::connection()); $context=$scopes->context($entity,$id);
        if (!$context) { http_response_code(404); exit('Recurso nao encontrado.'); }
        if (!$scopes->allows((int)$u['id'],$permission,$context)) { AuditService::record('authorization_denied',$entity,$id,[],['permission'=>$permission,'result'=>'denied'],$context); http_response_code(403); exit('Acesso negado.'); }
        return $u;
    }
    public static function requireTournamentPermission(string $permission,int $tournamentId): array { return self::requirePermission($permission,'tournaments',$tournamentId); }
}
