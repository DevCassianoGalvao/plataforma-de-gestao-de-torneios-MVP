<?php
declare(strict_types=1);
namespace App\Support;

final class Session {
    public static function start(): void { if (session_status() === PHP_SESSION_ACTIVE) return; session_name((string) Env::get('SESSION_NAME','torneios_session')); session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','path'=>'/']); session_start(); }
    public static function get(string $key, mixed $default=null): mixed { self::start(); return $_SESSION[$key] ?? $default; }
    public static function set(string $key, mixed $value): void { self::start(); $_SESSION[$key] = $value; }
    public static function forget(string $key): void { self::start(); unset($_SESSION[$key]); }
    public static function flash(string $key, string $value): void { self::set('_flash_'.$key, $value); }
    public static function takeFlash(string $key): ?string { $v=self::get('_flash_'.$key); self::forget('_flash_'.$key); return $v; }
    public static function user(): ?array { return self::get('user'); }
    public static function login(array $user): void { self::start(); session_regenerate_id(true); self::set('user', $user); }
    public static function logout(): void { self::start(); $_SESSION=[]; session_destroy(); }
}
