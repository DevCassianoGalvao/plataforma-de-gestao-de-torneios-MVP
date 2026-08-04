<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\Config;
final class GoogleDriveBackupProvider implements BackupRemoteProvider {
    private function token(): string { return trim((string)Config::get('GOOGLE_DRIVE_ACCESS_TOKEN','')); }
    private function folder(): string { return trim((string)Config::get('GOOGLE_DRIVE_FOLDER_ID','')); }
    public function testConnection(): array { if($this->token()===''||$this->folder()==='')return ['ok'=>false,'error'=>'Google Drive nao configurado.']; return $this->request('GET','https://www.googleapis.com/drive/v3/files/'.$this->folder().'?fields=id'); }
    public function upload(string $path,string $name,string $hash): array { if(!is_file($path))return ['ok'=>false,'error'=>'Arquivo local ausente.']; $meta=json_encode(['name'=>$name,'parents'=>[$this->folder()],'appProperties'=>['sha256'=>$hash]],JSON_UNESCAPED_SLASHES); $body="--backup\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n$meta\r\n--backup\r\nContent-Type: application/zip\r\n\r\n".file_get_contents($path)."\r\n--backup--"; return $this->request('POST','https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart','multipart/related; boundary=backup',$body); }
    public function delete(string $remoteId): bool { return ($this->request('DELETE','https://www.googleapis.com/drive/v3/files/'.rawurlencode($remoteId))['ok']??false); }
    public function exists(string $remoteId): bool { return ($this->request('GET','https://www.googleapis.com/drive/v3/files/'.rawurlencode($remoteId).'?fields=id')['ok']??false); }
    public function list(): array { $query = "'" . $this->folder() . "' in parents"; return ($this->request('GET','https://www.googleapis.com/drive/v3/files?q='.rawurlencode($query).'&fields=files(id,name,size,createdTime)')['files'] ?? []); }
    private function request(string $method,string $url,?string $contentType=null,?string $body=null): array { if($this->token()===''||!function_exists('curl_init'))return ['ok'=>false,'error'=>'Google Drive nao configurado ou cURL indisponivel.']; $ch=curl_init($url); curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>array_filter(['Authorization: Bearer '.$this->token(),$contentType?'Content-Type: '.$contentType:null]),CURLOPT_TIMEOUT=>45]);if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);$response=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);$data=json_decode((string)$response,true);return $status>=200&&$status<300?array_merge(['ok'=>true],is_array($data)?$data:[]):['ok'=>false,'error'=>$error?:('Google Drive respondeu HTTP '.$status)]; }
}
