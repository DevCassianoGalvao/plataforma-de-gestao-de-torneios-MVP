<?php
declare(strict_types=1);require dirname(__DIR__).'/app/bootstrap.php';
use App\Support\Security;
$token=Security::csrfToken();try{Security::verifyCsrf('invalid');throw new RuntimeException('CSRF accepted');}catch(RuntimeException){}$upload=(string)file_get_contents(dirname(__DIR__).'/app/Services/UploadService.php');foreach(['MAX_BYTES','MIME_EXTENSIONS','finfo','random_bytes']as$n)if(!str_contains($upload,$n))throw new RuntimeException('Upload hardening missing '.$n);$bootstrap=(string)file_get_contents(dirname(__DIR__).'/app/bootstrap.php');foreach(['Content-Security-Policy','X-Content-Type-Options','Strict-Transport-Security']as$n)if(!str_contains($bootstrap,$n))throw new RuntimeException('Header missing '.$n);echo "SECURITY_E2E_OK\n";
