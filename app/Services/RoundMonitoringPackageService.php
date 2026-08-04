<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\RoundMonitoringRepository;

final class RoundMonitoringPackageService
{
    public function __construct(private readonly RoundMonitoringRepository $rounds, private readonly StorageService $storage, private readonly AuditService $audit) {}

    public function create(int $roundId, int $userId): array
    {
        if (!class_exists('ZipArchive')) return ['ok'=>false,'errors'=>['Extensão ZIP não disponível.']];
        $temporary=tempnam(sys_get_temp_dir(),'round-package-'); if($temporary===false)return ['ok'=>false,'errors'=>['Não foi possível preparar o pacote.']];
        $zip=new \ZipArchive(); if($zip->open($temporary,\ZipArchive::CREATE|\ZipArchive::OVERWRITE)!==true){@unlink($temporary);return ['ok'=>false,'errors'=>['Não foi possível criar o pacote.']];}
        $count=0; $reportNames=[];
        foreach($this->rounds->packageFiles($roundId) as $row){
            if(!empty($row['report_path'])&&!isset($reportNames[$row['report_path']])){$file=$this->storage->read($row['report_path']);if($file){$zip->addFromString('sumulas/'.($row['report_name']?:'partida-'.$row['match_id'].'.pdf'),$file['body']);$count++;}$reportNames[$row['report_path']]=true;}
            if(!empty($row['evidence_path'])){$file=$this->storage->read($row['evidence_path']);if($file){$name=preg_replace('/[^A-Za-z0-9._-]+/','-',(string)($row['evidence_name']?:basename($row['evidence_path'])))?:'arquivo';$zip->addFromString('evidencias/partida-'.$row['match_id'].'/'.$name,$file['body']);$count++;}}
        }
        $csv=$this->csv($this->rounds->exportRows($roundId));$zip->addFromString('pendencias.csv',$csv);$zip->close();
        $contents=file_get_contents($temporary);@unlink($temporary);if($contents===false)return ['ok'=>false,'errors'=>['Não foi possível ler o pacote.']];
        $stored=$this->storage->storeContents($contents,'round-monitoring/packages','zip','application/zip');$this->audit->record('round_monitor.package_created',$userId,'competition_round',$roundId,['files'=>$count],null);
        return ['ok'=>true,'errors'=>[],'file'=>$stored,'name'=>'pacote-documental-rodada-'.$roundId.'.zip'];
    }

    private function csv(array $rows): string {if($rows===[])return "\xEF\xBB\xBF\n";$out=fopen('php://temp','r+');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,array_keys($rows[0]),';');foreach($rows as $row)fputcsv($out,$row,';');rewind($out);return stream_get_contents($out)?:'';}
}
