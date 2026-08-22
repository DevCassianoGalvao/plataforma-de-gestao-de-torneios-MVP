<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ChampionshipRepository;
use App\Repositories\EventDayRepository;
use App\Repositories\ScheduleRepository;
use App\Services\StorageService;

final class EventDayController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly EventDayRepository $days, private readonly ChampionshipRepository $championships, private readonly ScheduleRepository $schedules, private readonly StorageService $storage)
    { parent::__construct($users,$authorization,$audit); }

    public function index(Request $request,array $params=[]): Response
    {
        $user=$this->guardAny($request,['evidence.checklist.manage','evidence.upload']);if($user instanceof Response)return $user;
        $canManage=$this->authorization->can($user,'evidence.checklist.manage');
        $championships=$this->championships->listForUser((int)$user['id'],true);
        $championshipId=(int)($request->query['championship_id']??($championships[0]['id']??0));
        $championship=$this->championships->findForUser($championshipId,(int)$user['id'],true);if(!$championship)return Response::forbidden();
        $eventDays = $this->days->list($championshipId);
        foreach ($eventDays as &$eventDay) {
            $eventDay['media'] = $this->days->media((int) $eventDay['id']);
        }
        unset($eventDay);
        return $this->page('Dias de evento','admin/event-days/index',['user'=>$user,'canManage'=>$canManage,'championships'=>$championships,'championship'=>$championship,'eventDays'=>$eventDays,'venues'=>$this->schedules->listVenues($championshipId),'checklist'=>$this->days->checklist($championshipId),'message'=>Session::consumeFlash('event_day_message')]);
    }

    public function create(Request $request,array $params=[]): Response
    {
        $user=$this->guard($request,'evidence.checklist.manage');if($user instanceof Response)return $user;if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');
        $championshipId=(int)($request->body['championship_id']??0);$championship=$this->championships->findForUser($championshipId,(int)$user['id'],true);if(!$championship)return Response::forbidden();
        $date=trim((string)($request->body['event_date']??''));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))return $this->errorPage('Dias de evento','errors/simple',['message'=>'Informe uma data válida.'],422);
        $venueId=(int)($request->body['venue_id']??0);if($venueId>0&&!$this->venueBelongs($venueId,$championshipId))return $this->errorPage('Dias de evento','errors/simple',['message'=>'O local não pertence a este campeonato.'],422);
        $id=$this->days->create(['championship_id'=>$championshipId,'venue_id'=>$venueId?:null,'event_date'=>$date,'name'=>trim((string)($request->body['name']??'')),'notes'=>trim((string)($request->body['notes']??''))],(int)$user['id']);
        $this->audit->record('event_day.created',(int)$user['id'],'event_day',$id,['championship_id'=>$championshipId],$request);Session::flash('event_day_message','Dia de evento cadastrado.');return Response::redirect(Config::url('/admin/dias-evento?championship_id='.$championshipId));
    }

    public function delete(Request $request,array $params=[]): Response
    {
        $user=$this->guard($request,'evidence.checklist.manage');if($user instanceof Response)return $user;if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');
        $id=(int)($params[0]??0);$championshipId=(int)($request->body['championship_id']??0);if(!$this->days->find($id,$championshipId))return Response::forbidden();$this->days->delete($id,$championshipId,(int)$user['id']);Session::flash('event_day_message','Dia de evento arquivado.');return Response::redirect(Config::url('/admin/dias-evento?championship_id='.$championshipId));
    }

    public function upload(Request $request,array $params=[]): Response
    {
        $user=$this->guard($request,'evidence.upload');if($user instanceof Response)return $user;if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');
        $day=$this->dayForUser($user,(int)($params[0]??0));if(!$day)return Response::forbidden();$itemId=(int)($request->body['checklist_item_id']??0);$item=$itemId?$this->days->item($itemId,(int)$day['championship_id']):null;if($itemId&&!$item)return Response::html('Item de evidência inválido.',422);
        $files=$this->files($request->files['files']??[]);if($files===[])return Response::html('Selecione ao menos um arquivo.',422);$created=[];
        try{foreach($files as $file){$mime=(new \finfo(FILEINFO_MIME_TYPE))->file((string)($file['tmp_name']??''));$allowed=['image/jpeg','image/png','image/webp','application/pdf'];if(!in_array($mime,$allowed,true))throw new \RuntimeException('Formato não permitido.');$stored=str_starts_with($mime,'image/')?$this->storage->storeOptimizedImage($file,'event-days/'.(int)$day['id'],['max_width'=>1920,'max_height'=>1440,'max_bytes'=>12582912]):$this->storage->store($file,'event-days/'.(int)$day['id'],['application/pdf'],12582912);$created[]=$this->days->createMedia(['event_day_id'=>$day['id'],'championship_id'=>$day['championship_id'],'checklist_item_id'=>$item['id']??null,'title'=>trim((string)($request->body['title']??''))?:($item['name']??'Evidência do dia'),'caption'=>trim((string)($request->body['caption']??'')),'storage_path'=>$stored['path'],'original_name'=>$stored['original_name'],'mime_type'=>$stored['mime'],'file_hash'=>hash_file('sha256',(string)$file['tmp_name']),'review_status'=>$item?'submitted':'approved','captured_at'=>trim((string)($request->body['captured_at']??'')),'uploaded_by'=>$user['id']]);}}
        catch(\Throwable $e){return Response::html('Não foi possível enviar a evidência: '.$e->getMessage(),422);}
        Session::flash('event_day_message',count($created).' evidência(s) enviada(s).');return Response::redirect(Config::url('/admin/dias-evento?championship_id='.$day['championship_id']));
    }

    public function asset(Request $request,array $params=[]): Response
    {$user=$this->guard($request,'evidence.download');if($user instanceof Response)return $user;$day=$this->dayForUser($user,(int)($params[0]??0));$media=$day?$this->days->findMedia((int)($params[1]??0),(int)$day['id']):null;if(!$media)return Response::html('Arquivo não encontrado.',404);$file=$this->storage->read((string)$media['storage_path']);return $file?Response::binary($file['body'],$file['mime'],$media['original_name']):Response::html('Arquivo não encontrado.',404);}

    public function review(Request $request,array $params=[]): Response
    {$user=$this->guard($request,'evidence.approve');if($user instanceof Response)return $user;if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');$day=$this->dayForUser($user,(int)($params[0]??0));$media=$day?$this->days->findMedia((int)($params[1]??0),(int)$day['id']):null;$decision=(string)($request->body['decision']??'');if(!$media||!in_array($decision,['approved','rejected'],true))return Response::html('Ação inválida.',422);$this->days->review((int)$media['id'],$decision,(int)$user['id'],trim((string)($request->body['reason']??''))?:null);Session::flash('event_day_message','Revisão registrada.');return Response::redirect(Config::url('/admin/dias-evento?championship_id='.$day['championship_id']));}

    public function remove(Request $request,array $params=[]): Response
    {$user=$this->guard($request,'evidence.remove');if($user instanceof Response)return $user;if(!$this->validCsrf($request))return Response::forbidden('A sessão expirou.');$day=$this->dayForUser($user,(int)($params[0]??0));$media=$day?$this->days->findMedia((int)($params[1]??0),(int)$day['id']):null;if(!$media||$media['review_status']==='approved')return Response::html('Esta evidência não pode ser removida.',422);$this->days->removeMedia((int)$media['id'],(int)$user['id'],trim((string)($request->body['reason']??'')));Session::flash('event_day_message','Evidência removida.');return Response::redirect(Config::url('/admin/dias-evento?championship_id='.$day['championship_id']));}

    private function dayForUser(array $user,int $id):?array{$s=$this->days;foreach($this->championships->listForUser((int)$user['id'],true) as $c){$day=$s->find($id,(int)$c['id']);if($day)return $day;}return null;}
    private function venueBelongs(int $id,int $championshipId):bool{foreach($this->schedules->listVenues($championshipId) as $v)if((int)$v['id']===$id)return true;return false;}
    private function files(array $files):array{if(!isset($files['name'])||!is_array($files['name']))return isset($files['tmp_name'])?[$files]:[];$out=[];foreach(array_keys($files['name']) as $i)$out[]=['name'=>$files['name'][$i]??'','type'=>$files['type'][$i]??'','tmp_name'=>$files['tmp_name'][$i]??'','error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$files['size'][$i]??0];return $out;}
}
