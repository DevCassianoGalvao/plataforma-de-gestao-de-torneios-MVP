<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\MatchMediaRepository;
use App\Services\MatchOperationAccessService;
use App\Services\StorageService;

final class MatchMediaController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly MatchMediaRepository $media, private readonly MatchOperationAccessService $access, private readonly StorageService $storage)
    { parent::__construct($users, $authorization, $audit); }

    public function index(Request $request, array $params=[]): Response
    {
        $user=$this->guard($request,'match_operation.view'); if($user instanceof Response)return $user;
        $match=$this->match($user,(int)($params[0]??0)); if(!$match)return Response::forbidden();
        return $this->page('Evidências da partida','admin/matches/media',['user'=>$user,'match'=>$match,'items'=>$this->media->list((int)$match['id']),'checklist'=>$this->media->checklist((int)$match['championship_id'],(int)$match['id']),'history'=>$this->media->history((int)$match['id']),'canUpload'=>$this->access->canOperate($user,$match)&&$this->authorization->can($user,'evidence.upload'),'canReview'=>$this->authorization->can($user,'evidence.review'),'canApprove'=>$this->authorization->can($user,'evidence.approve'),'canRemove'=>$this->authorization->can($user,'evidence.remove'),'message'=>Session::consumeFlash('media_message'),'errors'=>[]]);
    }

    public function upload(Request $request, array $params=[]): Response
    {
        $user=$this->guard($request,'evidence.upload'); if($user instanceof Response)return $user;
        $match=$this->match($user,(int)($params[0]??0),true); if(!$match)return Response::forbidden(); if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');
        $itemId=(int)($request->body['checklist_item_id']??0); $item=$itemId?$this->media->itemForMatch($itemId,(int)$match['championship_id']):null;
        if($itemId && !$item)return Response::html('Item do checklist inválido.',422);
        $files=$this->files($request->files['files']??$request->files['photo']??[]); if($files===[])return Response::html('Selecione ao menos um arquivo.',422);
        if($item && $this->media->countForItem((int)$match['id'],$itemId)+count($files)>(int)$item['max_files'])return Response::html('A quantidade máxima deste item será excedida.',422);
        $created=[];
        try { foreach($files as $file){ $created[]=$this->store($file,$match,$item,$user,$request,(int)($request->body['replace_media_id']??0)); } }
        catch(\Throwable $e){ foreach($created as $media)$this->storage->delete($media['path']); return Response::html('Não foi possível enviar a evidência: '.$e->getMessage(),422); }
        if(($old=(int)($request->body['replace_media_id']??0))>0 && count($created)===1){$oldMedia=$this->media->find($old);if($oldMedia&&(int)$oldMedia['match_id']===(int)$match['id']){$this->media->replace($old,(int)$created[0]['id']);$this->media->remove($old,(int)$user['id'],'Substituída por nova evidência.');$this->media->log((int)$match['id'],$old,$itemId,'substituicao','Arquivo substituído.',(int)$user['id']);}}
        Session::flash('media_message',count($created).' evidência(s) enviada(s) para revisão.'); return Response::redirect(Config::url('/admin/partidas/'.$match['id'].'/evidencias'));
    }
    public function updateNotes(Request $request,array $params=[]): Response
    { return $this->change($request,$params,'evidence.upload',function(array $user,array $match,int $id)use($request){$item=$this->media->find($id);if(!$item||(int)$item['match_id']!==(int)$match['id'])return false;$notes=trim((string)($request->body['caption']??''));$this->media->updateNotes($id,$notes);$this->media->log((int)$match['id'],$id,(int)$item['checklist_item_id'],'observacao_corrigida',$notes,(int)$user['id']);return true;},'Observação atualizada.'); }
    public function remove(Request $request,array $params=[]): Response
    { return $this->change($request,$params,'evidence.remove',function(array $user,array $match,int $id)use($request){$item=$this->media->find($id);if(!$item||(int)$item['match_id']!==(int)$match['id']||$item['review_status']==='approved')return false;$reason=trim((string)($request->body['reason']??''));$this->media->remove($id,(int)$user['id'],$reason);$this->media->log((int)$match['id'],$id,(int)$item['checklist_item_id'],'remocao',$reason,(int)$user['id']);return true;},'Evidência removida.'); }
    public function review(Request $request,array $params=[]): Response
    { return $this->change($request,$params,'evidence.approve',function(array $user,array $match,int $id)use($request){$item=$this->media->find($id);$status=(string)($request->body['decision']??'');$reason=trim((string)($request->body['reason']??''));if(!$item||(int)$item['match_id']!==(int)$match['id']||!in_array($status,['approved','rejected'],true)||($status==='rejected'&&$reason===''))return false;$this->media->review($id,(int)$user['id'],$status,$reason?:null);$this->media->log((int)$match['id'],$id,(int)$item['checklist_item_id'],$status==='approved'?'aprovacao':'rejeicao',$reason,(int)$user['id']);return true;},'Revisão registrada.'); }
    public function asset(Request $request,array $params=[]): Response
    { $user=$this->guard($request,'evidence.download');if($user instanceof Response)return $user;$item=$this->media->find((int)($params[1]??0));if(!$item)return Response::html('Arquivo não encontrado.',404);$match=$this->match($user,(int)$item['match_id']);if(!$match)return Response::forbidden();$file=$this->storage->read((string)$item['storage_path']);if(!$file)return Response::html('Arquivo não encontrado.',404);return Response::binary($file['body'],$file['mime'],$item['original_name']); }

    private function store(array $file,array $match,?array $item,array $user,Request $request,int $supersedes): array
    { $allowed=$item?array_values(array_filter(array_map('trim',explode(',',$item['allowed_mime_types'])))):['image/jpeg','image/png','image/webp'];$max=$item?(int)$item['max_file_size_bytes']:12582912;$mime=(new \finfo(FILEINFO_MIME_TYPE))->file((string)($file['tmp_name']??''));if(!in_array($mime,$allowed,true))throw new \RuntimeException('Formato não permitido para este item.');$stored=str_starts_with((string)$mime,'image/')?$this->storage->storeOptimizedImage($file,'match-media/'.(int)$match['id'],['max_width'=>1920,'max_height'=>1440,'max_bytes'=>$max]):$this->storage->store($file,'match-media/'.(int)$match['id'],$allowed,$max);$caption=trim((string)($request->body['caption']??''));if($item&&(int)$item['notes_required']===1&&$caption===''){ $this->storage->delete($stored['path']);throw new \RuntimeException('Este item exige observação.'); }$hash=hash_file('sha256',(string)($file['tmp_name']??''))?:null;$id=$this->media->create(['match_id'=>$match['id'],'championship_id'=>$match['championship_id'],'checklist_item_id'=>$item['id']??null,'title'=>trim((string)($request->body['title']??''))?:($item['name']??'Registro da partida'),'caption'=>$caption?:null,'storage_path'=>$stored['path'],'original_name'=>$stored['original_name'],'mime_type'=>$stored['mime'],'file_hash'=>$hash,'visibility'=>in_array($request->body['visibility']??'',['private','accountability','public'],true)?$request->body['visibility']:'accountability','review_status'=>$item?'submitted':'approved','captured_at'=>trim((string)($request->body['captured_at']??''))?:null,'uploaded_by'=>$user['id'],'supersedes_media_id'=>$supersedes?:null]);$this->media->log((int)$match['id'],$id,$item['id']??null,'upload','Arquivo enviado.',(int)$user['id']);$this->audit->record('match_media.uploaded',(int)$user['id'],'match_media',$id,['match_id'=>(int)$match['id'],'mime'=>$stored['mime'],'size'=>$stored['size'],'hash'=>$hash],$request);return ['id'=>$id,'path'=>$stored['path']]; }
    private function files(array $files): array { if(!isset($files['name'])||!is_array($files['name']))return isset($files['tmp_name'])?[$files]:[];$out=[];foreach(array_keys($files['name']) as $i)$out[]=['name'=>$files['name'][$i]??'','type'=>$files['type'][$i]??'','tmp_name'=>$files['tmp_name'][$i]??'','error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$files['size'][$i]??0];return $out; }
    private function match(array $user,int $id,bool $operation=false): ?array {$match=$this->access->matchForUser($user,$id);return $match&&($operation?$this->access->canOperate($user,$match):$this->access->canView($user,$match))?$match:null;}
    private function change(Request $request,array $params,string $permission,callable $callback,string $message): Response {$user=$this->guard($request,$permission);if($user instanceof Response)return $user;$match=$this->match($user,(int)($params[0]??0),$permission==='evidence.upload'||$permission==='evidence.remove');if(!$match)return Response::forbidden();if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');if(!$callback($user,$match,(int)($params[1]??0)))return Response::html('Ação não permitida para esta evidência.',422);Session::flash('media_message',$message);return Response::redirect(Config::url('/admin/partidas/'.$match['id'].'/evidencias'));}
}
