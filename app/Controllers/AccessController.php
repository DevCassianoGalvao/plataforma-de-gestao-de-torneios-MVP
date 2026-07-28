<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Policies\AuthPolicy;
use App\Services\AuditService;
use App\Support\Database;
use App\Support\Security;
use App\Support\View;

final class AccessController
{
    public function index(): string { AuthPolicy::requireSuperAdmin(); $db=Database::connection(); return View::render('admin/access-control',['title'=>'Acessos e auditoria','roles'=>$db->query('SELECT r.*,GROUP_CONCAT(p.permission_key ORDER BY p.permission_key SEPARATOR ", ") permissions FROM roles r LEFT JOIN role_permission_assignments rp ON rp.role_id=r.id LEFT JOIN permissions p ON p.id=rp.permission_id GROUP BY r.id ORDER BY r.name')->fetchAll(),'assignments'=>$db->query('SELECT a.*,u.name user_name,u.email,r.name role_name FROM user_role_assignments a JOIN users u ON u.id=a.user_id JOIN roles r ON r.id=a.role_id WHERE a.deleted_at IS NULL ORDER BY a.id DESC')->fetchAll(),'users'=>$db->query('SELECT id,name,email FROM users WHERE deleted_at IS NULL ORDER BY name')->fetchAll(),'audit'=>$db->query('SELECT a.*,u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 100')->fetchAll()]); }
    public function assign(): never { AuthPolicy::requireSuperAdmin(); Security::verifyCsrf($_POST['_csrf']??null); $db=Database::connection(); $data=['user_id'=>(int)($_POST['user_id']??0),'role_id'=>(int)($_POST['role_id']??0),'organization_id'=>(int)($_POST['organization_id']??0)?:null,'project_id'=>(int)($_POST['project_id']??0)?:null,'tournament_id'=>(int)($_POST['tournament_id']??0)?:null,'team_id'=>(int)($_POST['team_id']??0)?:null]; if(!$data['user_id']||!$data['role_id']){http_response_code(422);exit('Usuário e perfil são obrigatórios.');}$s=$db->prepare('INSERT INTO user_role_assignments(user_id,role_id,organization_id,project_id,tournament_id,team_id,status,created_at,updated_at) VALUES(?,?,?,?,?,?,"active",NOW(),NOW())');$s->execute(array_values($data));$id=(int)$db->lastInsertId(); AuditService::record('scope_assignment','user_role_assignments',$id,[],$data,['organization_id'=>$data['organization_id'],'project_id'=>$data['project_id'],'tournament_id'=>$data['tournament_id'],'team_id'=>$data['team_id']]);Security::redirect('/admin/access'); }
}
