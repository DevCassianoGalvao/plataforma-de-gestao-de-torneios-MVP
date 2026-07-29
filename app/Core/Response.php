<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public readonly array $headers;

    public function __construct(
        public readonly string $body,
        public readonly int $status = 200,
        array $headers = [],
    ) {
        $this->headers = array_merge(self::securityHeaders(), $headers);
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(array $payload, int $status = 200): self
    {
        return new self(
            (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public static function forbidden(string $message = 'Acesso negado.'): self
    {
        return self::html(View::page('Acesso negado', View::render('errors/403', ['message' => $message])), 403);
    }

    public static function binary(string $body, string $contentType, string $downloadName = ''): self
    {
        $headers = ['Content-Type' => $contentType, 'Cache-Control' => 'private, max-age=3600'];
        if ($downloadName !== '') {
            $headers['Content-Disposition'] = 'inline; filename="' . addcslashes($downloadName, '"\\') . '"';
        }
        return new self($body, 200, $headers);
    }

    public function send(): never
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
        exit;
    }

    private static function securityHeaders(): array
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Content-Security-Policy' => "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self'; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'",
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];
        if (Config::bool('APP_HSTS', true) && self::isHttps()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }
        return $headers;
    }

    private static function isHttps(): bool
    {
        return ($_SERVER['HTTPS'] ?? '') === 'on' || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
    }
}
