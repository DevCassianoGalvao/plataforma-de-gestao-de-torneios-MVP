<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $timeoutChecked = false;

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        $savePath = Config::get('SESSION_SAVE_PATH', dirname(__DIR__, 2) . '/storage/sessions') ?: dirname(__DIR__, 2) . '/storage/sessions';
        if (preg_match('#^[A-Za-z]:[\\\\/]#', $savePath) !== 1 && !str_starts_with($savePath, '/')) {
            $savePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, trim($savePath, '/\\'));
        }
        if (is_dir($savePath)) {
            ini_set('session.save_path', $savePath);
        }
        session_name(Config::get('SESSION_NAME', 'torneios_mvp_session'));
        $sameSite = Config::get('SESSION_SAMESITE', 'Lax') ?: 'Lax';
        if (!in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
            $sameSite = 'Lax';
        }
        $secure = Config::bool('SESSION_SECURE_COOKIE', self::isHttps());
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => Config::basePath() ?: '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);
        session_start();
        self::expireInactiveSession();
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::put('_flash.' . $key, $value);
    }

    public static function consumeFlash(string $key, mixed $default = null): mixed
    {
        $value = self::get('_flash.' . $key, $default);
        self::forget('_flash.' . $key);
        return $value;
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        self::$timeoutChecked = false;
    }

    private static function expireInactiveSession(): void
    {
        if (self::$timeoutChecked) {
            return;
        }
        self::$timeoutChecked = true;
        $userId = $_SESSION['user_id'] ?? null;
        $lastActivity = $_SESSION['last_activity'] ?? null;
        $timeout = (int) (Config::get('SESSION_TIMEOUT', '1800') ?? '1800');
        if ($userId !== null && is_int($lastActivity) && $timeout > 0 && $lastActivity + $timeout < time()) {
            self::destroy();
            return;
        }
        if ($userId !== null) {
            $_SESSION['last_activity'] = time();
        }
    }

    private static function isHttps(): bool
    {
        return ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }
}
