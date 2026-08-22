<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Core\Config; use App\Core\Request; use App\Core\Response; use App\Core\Session; use App\Repositories\EvidenceChecklistRepository;

final class EvidenceChecklistController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly EvidenceChecklistRepository $items){parent::__construct($users,$authorization,$audit);}
    public function index(Request $request,array $params=[]):Response {$user=$this->guard($request,'evidence.checklist.manage');if($user instanceof Response)return $user;$champ=$this->items->championship((string)($params[0]??''));if(!$champ)return Response::html('Campeonato não encontrado.',404);return $this->page('Checklist de evidências','admin/evidence/checklist',['user'=>$user,'championship'=>$champ,'championships'=>$this->items->championships(),'items'=>$this->items->items((int)$champ['id'],true),'message'=>Session::consumeFlash('evidence_checklist_message')]);}
    public function save(Request $request,array $params=[]):Response{return $this->mutate($request,$params,function(array $user,array $champ)use($request){$data=$this->input($request);$id=(int)($request->body['item_id']??0);if($data===null)return null;$saved=$this->items->save((int)$champ['id'],$data,(int)$user['id'],$id?:null);$this->audit->record($id?'evidence.checklist.updated':'evidence.checklist.created',(int)$user['id'],'championship_evidence_checklist_item',$saved,['championship_id'=>$champ['id']],$request);return $saved;},'Checklist salvo.');}
    public function toggle(Request $request,array $params=[]):Response{return $this->mutate($request,$params,function(array $user,array $champ)use($request){$id=(int)($params[1]??0);$active=($request->body['active']??'')==='1';if(!$this->items->item($id,(int)$champ['id'])||!$this->items->toggle($id,(int)$champ['id'],$active))return null;$this->audit->record('evidence.checklist.'.($active?'activated':'deactivated'),(int)$user['id'],'championship_evidence_checklist_item',$id,[],$request);return $id;},'Situação do item atualizada.');}
    public function delete(Request $request,array $params=[]):Response{return $this->mutate($request,$params,function(array $user,array $champ)use($request){$id=(int)($params[1]??0);if(!$this->items->delete($id,(int)$champ['id'],(int)$user['id']))return null;$this->audit->record('evidence.checklist.deleted',(int)$user['id'],'championship_evidence_checklist_item',$id,[],$request);return $id;},'Item removido do checklist.');}
    public function restore(Request $request,array $params=[]):Response{return $this->mutate($request,$params,function(array $user,array $champ)use($request){$id=(int)($params[1]??0);if(!$this->items->restore($id,(int)$champ['id']))return null;$this->audit->record('evidence.checklist.restored',(int)$user['id'],'championship_evidence_checklist_item',$id,[],$request);return $id;},'Item restaurado.');}
    public function reorder(Request $request,array $params=[]):Response{return $this->mutate($request,$params,function(array $user,array $champ)use($request){$orders=(array)($request->body['order']??[]);asort($orders,SORT_NUMERIC);$this->items->reorder((int)$champ['id'],array_keys($orders));$this->audit->record('evidence.checklist.reordered',(int)$user['id'],'championship',$champ['id'],[],$request);return 1;},'Ordem atualizada.');}
    public function duplicate(Request $request,array $params=[]):Response
    {
        $user=$this->guard($request,'evidence.checklist.manage'); if($user instanceof Response)return $user;
        $champ=$this->items->championship((string)($params[0]??'')); if(!$champ)return Response::html('Campeonato não encontrado.',404);
        if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');
        $from=(int)($request->body['source_championship_id']??0); $source=$this->items->championshipById($from);
        if(!$source||$from===(int)$champ['id'])return Response::html('Selecione um campeonato de origem válido.',422);
        if($this->items->activeCount((int)$champ['id'])>0)return Response::html('O campeonato de destino já possui itens configurados. Remova ou mantenha o checklist atual antes de duplicar.',422);
        $count=$this->items->duplicate($from,(int)$champ['id'],(int)$user['id']);
        if($count===null)return Response::html('O checklist de destino foi alterado antes da duplicação. Recarregue a página e tente novamente.',409);
        $this->audit->record('evidence.checklist.duplicated',(int)$user['id'],'championship',$champ['id'],['source_championship_id'=>$from,'items'=>$count],$request);
        Session::flash('evidence_checklist_message','Checklist duplicado com segurança.');
        return Response::redirect(Config::url('/admin/campeonatos/'.$champ['slug'].'/evidencias'));
    }
    public function applyPreset(Request $request,array $params=[]):Response
    {
        $user=$this->guard($request,'evidence.checklist.manage'); if($user instanceof Response)return $user;
        $champ=$this->items->championship((string)($params[0]??'')); if(!$champ)return Response::html('Campeonato não encontrado.',404);
        if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');
        $count=$this->items->applyFootballAccountabilityPreset((int)$champ['id'],(int)$user['id']);
        if($count===0)return Response::html('Este campeonato já possui itens ativos no checklist. Use a duplicação ou edite os itens existentes.',409);
        $this->audit->record('evidence.checklist.preset_applied',(int)$user['id'],'championship',$champ['id'],['items'=>$count],$request);
        Session::flash('evidence_checklist_message','Modelo de prestação de contas aplicado com '.$count.' itens.');
        return Response::redirect(Config::url('/admin/campeonatos/'.$champ['slug'].'/evidencias'));
    }
    private function mutate(Request $request,array $params,callable $callback,string $message):Response {$user=$this->guard($request,'evidence.checklist.manage');if($user instanceof Response)return $user;$champ=$this->items->championship((string)($params[0]??''));if(!$champ)return Response::html('Campeonato não encontrado.',404);if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');if($callback($user,$champ)===null)return Response::html('Dados do checklist inválidos.',422);Session::flash('evidence_checklist_message',$message);return Response::redirect(Config::url('/admin/campeonatos/'.$champ['slug'].'/evidencias'));}
    private function input(Request $r): ?array
    {
        $name = trim((string) ($r->body['name'] ?? ''));
        $min = filter_var($r->body['min_files'] ?? 1, FILTER_VALIDATE_INT);
        $max = filter_var($r->body['max_files'] ?? 1, FILTER_VALIDATE_INT);
        $bytes = filter_var($r->body['max_file_size_bytes'] ?? 10485760, FILTER_VALIDATE_INT);
        $moment = (string) ($r->body['expected_moment'] ?? 'after_match');
        $mimes = array_values(array_filter(array_map('trim', explode(',', (string) ($r->body['allowed_mime_types'] ?? '')))));
        if ($name === '' || $min === false || $max === false || $min < 1 || $max < $min || $bytes === false || $bytes < 1024 || !in_array($moment, ['before_match', 'during_match', 'after_match', 'final_documentation', 'event_day'], true) || $mimes === []) return null;
        $flag = static fn (string $key): int => (($r->body[$key] ?? '') === '1' ? 1 : 0);
        $scope = (string)($r->body['scope'] ?? 'match');
        if (!in_array($scope, ['match', 'event_day'], true)) return null;
        return ['scope'=>$scope,'name'=>$name,'description'=>trim((string)($r->body['description']??''))?:null,'is_required'=>$flag('is_required'),'is_active'=>$flag('is_active'),'display_order'=>max(1,(int)($r->body['display_order']??1)),'expected_moment'=>$moment,'allowed_mime_types'=>implode(',',$mimes),'min_files'=>$min,'max_files'=>$max,'max_file_size_bytes'=>$bytes,'notes_required'=>$flag('notes_required'),'blocks_operation_start'=>$flag('blocks_operation_start'),'blocks_approval_submission'=>$flag('blocks_approval_submission'),'blocks_document_completion'=>$flag('blocks_document_completion'),'show_in_accountability'=>$flag('show_in_accountability')];
    }
}
