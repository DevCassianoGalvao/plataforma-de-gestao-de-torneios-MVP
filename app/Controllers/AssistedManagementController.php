<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Policies\AuthPolicy;
use App\Services\AssistedAdministrationService;
use App\Services\ProductNavigationService;
use App\Services\RuleConfigurationService;
use App\Services\TournamentOperationService;
use App\Services\UploadService;
use App\Support\Database;
use App\Support\Security;
use App\Support\Session;
use App\Support\View;

final class AssistedManagementController
{
    private function nav(): ProductNavigationService { return new ProductNavigationService(Database::connection()); }
    public function page(array $p): string {
        $user=AuthPolicy::requireUser(); $t=$this->nav()->tournament($user,(string)$p['championship']); if(!$t)return $this->notFound();
        $module=(string)$p['module']; if(!in_array($module,['equipes','atletas','comissao','responsaveis','inscricoes','documentos','configuracoes'],true)||!$this->nav()->canUseTournamentModule($user,$t,$module))return $this->forbidden();
        return View::render('admin/assisted-management',['title'=>ucfirst($module),'user'=>$user,'tournament'=>$t,'module'=>$module,'navigation'=>$this->nav()->menu($user,$t),'data'=>$this->data((int)$t['id'],$module),'rules'=>(new RuleConfigurationService(Database::connection()))->active((int)$t['id']),'csrf'=>Security::csrfToken()]);
    }
    public function save(array $p): never {
        $user=AuthPolicy::requireUser(); $t=$this->nav()->tournament($user,(string)$p['championship']); if(!$t){http_response_code(404);exit('Recurso nao encontrado.');}
        try { Security::verifyCsrf($_POST['_csrf']??null); } catch (\RuntimeException) { http_response_code(403); exit('Solicitacao invalida.'); }
        $module=(string)$p['module']; $tid=(int)$t['id'];
        try { $this->saveModule($module,$tid,$user); Session::flash('success','Alteracao salva.'); } catch(\Throwable $e){Session::flash('error',$e->getMessage());}
        Security::redirect('/admin/campeonatos/'.$t['slug'].'/'.$module);
    }
    private function saveModule(string $module,int $tid,array $user): void {
        $db=Database::connection(); $service=new AssistedAdministrationService($db);
        if($module==='equipes'){AuthPolicy::requireTournamentPermission('manage_roster',$tid); if(!empty($_POST['team_id'])){$service->setTeamStatus($tid,(int)$_POST['team_id'],(string)($_POST['team_action']??''));return;}$service->createTeam($tid,$_POST);return;}
        if($module==='atletas'){AuthPolicy::requireTournamentPermission('manage_roster',$tid);$service->createAthlete($tid,$_POST);return;}
        if($module==='comissao'){AuthPolicy::requireTournamentPermission('manage_roster',$tid);$service->createStaff($tid,$_POST);return;}
        if($module==='responsaveis'){AuthPolicy::requireTournamentPermission('manage_roster',$tid);$person=$this->personForTournament($tid,(int)($_POST['person_id']??0));$db->prepare('INSERT INTO legal_guardians(person_id,full_name,document_number,phone,email,relationship_type,authorizations_json,terms_accepted_at,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,IF(?,NOW(),NULL),"active",NOW(),NOW())')->execute([(int)$person['id'],trim((string)$_POST['full_name']),trim((string)($_POST['document_number']??''))?:null,trim((string)($_POST['phone']??''))?:null,trim((string)($_POST['email']??''))?:null,trim((string)($_POST['relationship_type']??'Responsavel')),json_encode(['registration_authorized'=>!empty($_POST['authorized'])]),!empty($_POST['accepted'])?1:0]);return;}
        if($module==='inscricoes'){ $action=(string)($_POST['action']??'create'); if($action==='create'){AuthPolicy::requireTournamentPermission('manage_roster',$tid);$person=$this->personForTournament($tid,(int)($_POST['person_id']??0),(int)($_POST['team_id']??0));(new TournamentOperationService($db))->registerAthlete($tid,(int)$_POST['team_id'],(int)$person['id'],trim((string)($_POST['shirt_number']??''))?:null);return;} $registration=$this->registration($tid,(int)($_POST['registration_id']??0));$permission=in_array($action,['approved','rejected','suspended','cancelled'],true)?($action==='approved'?'approve_registration':'reject_registration'):'manage_roster';AuthPolicy::requireTournamentPermission($permission,$tid);(new TournamentOperationService($db))->setRegistrationStatus((int)$registration['id'],$action,(int)$user['id'],trim((string)($_POST['reason']??''))?:null);return; }
        if($module==='documentos'){AuthPolicy::requireTournamentPermission('create',$tid);$title=trim((string)($_POST['title']??''));if($title==='')throw new \RuntimeException('Titulo obrigatorio.');$visibility=($_POST['visibility']??'private')==='public'?'public':'private';$path=(new UploadService())->store($_FILES['file']??[],$visibility);$db->prepare('INSERT INTO documents(tournament_id,title,document_category,file_path,visibility,status,expires_at,created_at,updated_at) VALUES(?,?,?,?,?,"active",?,NOW(),NOW())')->execute([$tid,$title,trim((string)($_POST['document_category']??'anexo')),$path,$visibility,trim((string)($_POST['expires_at']??''))?:null]);return;}
        if($module==='configuracoes'){AuthPolicy::requireTournamentPermission('create_regulation_version',$tid);$rules=(new RuleConfigurationService($db))->active($tid);if(isset($_POST['preset']))$rules=RuleConfigurationService::copaBrasilPreset();else{$rules['format']['groups_count']=(int)$_POST['groups_count'];$rules['points']['win']=(int)$_POST['points_win'];$rules['points']['draw']=(int)$_POST['points_draw'];$rules['discipline']['yellow_limit']=(int)$_POST['yellow_limit'];$rules['substitutions']['max_used']=(int)$_POST['substitutions'];$rules['knockout']['penalties']=!empty($_POST['penalties']);}$reason=trim((string)($_POST['reason']??''));(new RuleConfigurationService($db))->save($tid,$rules,$reason,(int)$user['id'],!empty($_POST['authorize_after_start']));return;}
        throw new \RuntimeException('Modulo invalido.');
    }
    private function data(int $tid,string $module): array { $db=Database::connection(); $q=fn($sql,$args=[])=>($s=$db->prepare($sql))&&$s->execute($args)?$s->fetchAll():[];
        $teams=$q('SELECT t.id,t.name,t.short_name,t.status,e.status entry_status,c.name category_name FROM team_tournament_entries e JOIN teams t ON t.id=e.team_id LEFT JOIN categories c ON c.id=e.category_id WHERE e.tournament_id=? AND e.deleted_at IS NULL ORDER BY t.name',[$tid]);
        $people=$q('SELECT p.id,p.full_name,p.public_name,p.person_type,p.birth_date,p.status,t.name team_name,tm.team_id,pp.primary_position,pp.preferred_number FROM people p JOIN team_memberships tm ON tm.person_id=p.id JOIN teams t ON t.id=tm.team_id JOIN team_tournament_entries e ON e.team_id=t.id LEFT JOIN person_profiles pp ON pp.person_id=p.id WHERE e.tournament_id=? AND tm.status="active" AND tm.deleted_at IS NULL AND p.deleted_at IS NULL ORDER BY p.full_name',[$tid]);
        $athletes=array_values(array_filter($people,fn(array $person): bool => (string)($person['person_type'] ?? 'athlete')==='athlete'));
        $staff=$q('SELECT p.id,p.full_name,t.name team_name,m.role FROM people p JOIN team_memberships m ON m.person_id=p.id JOIN teams t ON t.id=m.team_id JOIN team_tournament_entries e ON e.team_id=t.id WHERE e.tournament_id=? AND p.person_type="staff" AND m.status="active" AND m.deleted_at IS NULL AND p.deleted_at IS NULL ORDER BY p.full_name',[$tid]);
        return ['teams'=>$teams,'people'=>$people,'athletes'=>$athletes,'staff'=>$staff,'categories'=>$q('SELECT id,name FROM categories WHERE status="active" AND deleted_at IS NULL ORDER BY display_order,name'),'registrations'=>$q('SELECT r.id,r.status,r.shirt_number,r.rejection_reason,p.full_name,t.name team_name FROM registrations r JOIN people p ON p.id=r.person_id JOIN teams t ON t.id=r.team_id WHERE r.tournament_id=? AND r.deleted_at IS NULL ORDER BY r.updated_at DESC',[$tid]),'guardians'=>$q('SELECT g.*,p.full_name athlete_name FROM legal_guardians g JOIN people p ON p.id=g.person_id JOIN team_memberships tm ON tm.person_id=p.id JOIN team_tournament_entries e ON e.team_id=tm.team_id WHERE e.tournament_id=? AND g.deleted_at IS NULL ORDER BY g.id DESC',[$tid]),'documents'=>$q('SELECT id,title,document_category,visibility,status,expires_at FROM documents WHERE tournament_id=? AND deleted_at IS NULL ORDER BY id DESC',[$tid])]; }
    private function personForTournament(int $tid,int $personId,int $teamId=0): array {$db=Database::connection();$sql='SELECT p.id FROM people p JOIN team_memberships m ON m.person_id=p.id JOIN team_tournament_entries e ON e.team_id=m.team_id WHERE e.tournament_id=? AND p.id=? AND p.deleted_at IS NULL AND m.deleted_at IS NULL';$args=[$tid,$personId];if($teamId){$sql.=' AND m.team_id=?';$args[]=$teamId;}$s=$db->prepare($sql);$s->execute($args);return$s->fetch()?:throw new \RuntimeException('Atleta fora do escopo selecionado.');}
    private function registration(int $tid,int $id): array {$s=Database::connection()->prepare('SELECT id FROM registrations WHERE id=? AND tournament_id=? AND deleted_at IS NULL');$s->execute([$id,$tid]);return$s->fetch()?:throw new \RuntimeException('Inscricao fora do escopo.');}
    private function forbidden(): string {http_response_code(403);return View::render('errors/403',['title'=>'Acesso negado']);}
    private function notFound(): string {http_response_code(404);return View::render('errors/404',['title'=>'Pagina nao encontrada']);}
}
