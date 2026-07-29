<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

final class SensitiveData
{
    public static function encrypt(string $value): string
    {
        $key = self::key();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(trim($value), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) throw new \RuntimeException('Nao foi possivel proteger o dado pessoal.');
        return 'v1:' . base64_encode($iv . $tag . $ciphertext);
    }

    public static function mask(string $ciphertext): string
    {
        return $ciphertext === '' ? '' : 'Documento protegido';
    }

    private static function key(): string
    {
        $configured = Config::get('APP_KEY');
        if (($configured === null || $configured === '') && Config::get('APP_ENV', 'development') === 'production') {
            throw new \RuntimeException('APP_KEY e obrigatoria em producao.');
        }
        $configured = $configured ?: 'development-only-key-change-me';
        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
            if (is_string($decoded) && strlen($decoded) >= 32) return substr($decoded, 0, 32);
        }
        return hash('sha256', $configured, true);
    }
}
