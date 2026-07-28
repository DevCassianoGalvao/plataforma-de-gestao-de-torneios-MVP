<?php
declare(strict_types=1);
use App\Support\Env;
use App\Support\Session;
spl_autoload_register(function(string $class): void { if (!str_starts_with($class,'App\\')) return; $file=__DIR__.'/'.str_replace('\\','/',substr($class,4)).'.php'; if (is_file($file)) require $file; });
Env::load(dirname(__DIR__).'/.env'); Session::start();
header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');header('Referrer-Policy: strict-origin-when-cross-origin');header('Permissions-Policy: camera=(), microphone=(), geolocation=()');header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; script-src 'self'; frame-ancestors 'none'; base-uri 'self'");if(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
class_alias(\App\Support\View::class, 'View');
class_alias(\App\Support\Security::class, 'Security');
set_exception_handler(function(Throwable $e): void { App\Support\Logger::write('error','uncaught_exception',['message'=>$e->getMessage(),'trace'=>$e->getTraceAsString()]); if(PHP_SAPI==='cli'){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);} http_response_code(500); echo App\Support\View::render('errors/500',['title'=>'Erro interno','debug'=>Env::bool('APP_DEBUG') ? $e->getMessage() : null]); });
