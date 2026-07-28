<?php
declare(strict_types=1);
namespace App\Support;

final class Security {
    public static function csrfToken(): string { $token=Session::get('_csrf'); if (!$token) { $token=bin2hex(random_bytes(32)); Session::set('_csrf',$token); } return $token; }
    public static function verifyCsrf(?string $token): void { if (!$token || !hash_equals((string) Session::get('_csrf',''), $token)) throw new \RuntimeException('Token CSRF inválido.'); }
    public static function redirect(string $path): never { header('Location: '.rtrim((string) Env::get('APP_URL',''),'/').$path); exit; }
}
