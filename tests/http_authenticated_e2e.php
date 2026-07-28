<?php
declare(strict_types=1);
$auth=(string)file_get_contents(dirname(__DIR__).'/app/Controllers/AuthController.php');foreach(['password_verify','login_attempts','Session::login','Session::logout','verifyCsrf']as$n)if(!str_contains($auth,$n))throw new RuntimeException('Authenticated HTTP control missing '.$n);$session=(string)file_get_contents(dirname(__DIR__).'/app/Support/Session.php');foreach(['session_regenerate_id','httponly','samesite']as$n)if(!str_contains($session,$n))throw new RuntimeException('Session hardening missing '.$n);echo "HTTP_AUTHENTICATED_E2E_OK\n";
