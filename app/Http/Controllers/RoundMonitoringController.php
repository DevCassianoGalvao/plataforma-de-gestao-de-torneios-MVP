<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\RoundMonitoringRepository;
use App\Services\StorageService;
use App\Services\RoundMonitoringPackageService;

final class RoundMonitoringController extends Controller
{
    public function __construct($users, \App\Services\AuthorizationService $authorization, \App\Services\AuditService $audit, private readonly RoundMonitoringRepository $rounds, private readonly StorageService $storage, private readonly RoundMonitoringPackageService $packages) { parent::__construct($users,$authorization,$audit); }
    public function index(Request $request, array $params=[]): Response
    { $user=$this->guard($request,'round.monitor.view'); if($user instanceof Response)return $user; $admin=in_array('administrator',$this->authorization->roleKeys($user),true); $filters=array_merge($this->rounds->filters(),array_intersect_key($request->query,$this->rounds->filters())); return $this->page('Acompanhamento por rodada','admin/round-monitoring/index',['user'=>$user,'filters'=>$filters,'championships'=>$this->rounds->championshipsFor((int)$user['id'],$admin),'options'=>$this->rounds->options((int)$user['id'],$admin),'rounds'=>$this->rounds->rounds($filters,(int)$user['id'],$admin)]); }
    public function show(Request $request,array $params=[]): Response
    { $user=$this->guard($request,'round.monitor.view');if($user instanceof Response)return $user;$round=$this->rounds->round((int)($params[0]??0));$admin=in_array('administrator',$this->authorization->roleKeys($user),true);if(!$round||!$this->rounds->allowed((int)$round['championship_id'],(int)$user['id'],$admin))return Response::forbidden();$summary=$this->rounds->rounds(['round_id'=>$round['id']],(int)$user['id'],$admin)[0]??null;return $this->page('Detalhe da rodada','admin/round-monitoring/show',['user'=>$user,'round'=>$round,'summary'=>$summary,'matches'=>$this->rounds->matches((int)$round['id']),'canReport'=>$this->authorization->can($user,'round.report.generate'),'canManage'=>$this->authorization->can($user,'round.monitor.manage'),'canPackage'=>$this->authorization->can($user,'round.package.download')]); }
    public function deadline(Request $request,array $params=[]): Response
    { $user=$this->guard($request,'round.monitor.manage');if($user instanceof Response)return $user;if(!$this->validCsrf($request))return Response::forbidden('Sessao expirada.');$round=$this->rounds->round((int)($params[0]??0));$admin=in_array('administrator',$this->authorization->roleKeys($user),true);if(!$round||!$this->rounds->allowed((int)$round['championship_id'],(int)$user['id'],$admin))return Response::forbidden();$mode=(string)($request->body['deadline_mode']??'next_day');if(!in_array($mode,['same_day','next_day','hours','days'],true))$mode='next_day';$custom=in_array($mode,['hours','days'],true)?max(1,min(720,(int)($request->body['custom_value']??1))):null;$this->rounds->saveDeadline((int)$round['championship_id'],$mode,$custom,(int)$user['id']);$this->audit->record('round_monitor.deadline_saved',(int)$user['id'],'championship',(int)$round['championship_id'],['mode'=>$mode,'value'=>$custom],$request);Session::flash('round_monitor_message','Prazo documental atualizado para as rodadas do campeonato.');return Response::redirect(Config::url('/admin/rodadas/'.$round['id'].'/acompanhamento')); }
    public function export(Request $request,array $params=[]): Response
    { $user=$this->guard($request,'round.report.generate');if($user instanceof Response)return $user;$round=$this->rounds->round((int)($params[0]??0));$admin=in_array('administrator',$this->authorization->roleKeys($user),true);if(!$round||!$this->rounds->allowed((int)$round['championship_id'],(int)$user['id'],$admin))return Response::forbidden();$rows=$this->rounds->exportRows((int)$round['id']);$this->audit->record('round_monitor.exported',(int)$user['id'],'competition_round',(int)$round['id'],['rows'=>count($rows)],$request);return new Response($this->csv($rows),200,['Content-Type'=>'text/csv; charset=UTF-8','Content-Disposition'=>'attachment; filename="pendencias-rodada-'.$round['id'].'.csv"','Cache-Control'=>'private, no-store']); }
    public function package(Request $request,array $params=[]): Response
    { $user=$this->guard($request,'round.package.download');if($user instanceof Response)return $user;$round=$this->rounds->round((int)($params[0]??0));$admin=in_array('administrator',$this->authorization->roleKeys($user),true);if(!$round||!$this->rounds->allowed((int)$round['championship_id'],(int)$user['id'],$admin))return Response::forbidden();$result=$this->packages->create((int)$round['id'],(int)$user['id']);if(!$result['ok'])return Response::html(implode(' ',$result['errors']),422);$file=$this->storage->read($result['file']['path']);if(!$file)return Response::html('Pacote não encontrado.',404);return Response::binary($file['body'],'application/zip',$result['name']); }
    private function csv(array $rows): string {if($rows===[])return "\xEF\xBB\xBF\n";$out=fopen('php://temp','r+');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,array_keys($rows[0]),';');foreach($rows as $row)fputcsv($out,$row,';');rewind($out);return stream_get_contents($out)?:'';}
}
