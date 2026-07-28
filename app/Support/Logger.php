<?php
declare(strict_types=1);
namespace App\Support;
final class Logger { public static function write(string $level,string $event,array $context=[]): void {$dir=dirname(__DIR__,2).'/storage/logs';if(!is_dir($dir))mkdir($dir,0750,true);$context['level']=$level;$context['event']=$event;$context['timestamp']=gmdate('c');$context['correlation_id']=$_SERVER['HTTP_X_REQUEST_ID']??bin2hex(random_bytes(8));file_put_contents($dir.'/app-'.date('Y-m-d').'.log',json_encode($context,JSON_UNESCAPED_UNICODE).PHP_EOL,FILE_APPEND|LOCK_EX);} }
