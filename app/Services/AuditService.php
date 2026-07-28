<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\Database;
use App\Support\Session;

final class AuditService {
    public static function record(string $action, string $entity, ?int $entityId=null, array $before=[], array $after=[], ?array $context=null): void { $s=Database::connection()->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,organization_id,project_id,tournament_id,team_id,before_json,after_json,ip_address,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())'); $u=Session::user(); $context ??=(new ScopeService(Database::connection()))->context($entity,(int)$entityId); $s->execute([$u['id']??null,$action,$entity,$entityId,$context['organization_id']??null,$context['project_id']??null,$context['tournament_id']??null,$context['team_id']??null,json_encode($before,JSON_UNESCAPED_UNICODE),json_encode($after,JSON_UNESCAPED_UNICODE),$_SERVER['REMOTE_ADDR']??null]); }
}
