<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(
        public readonly string $body,
        public readonly int $status = 200,
        public readonly array $headers = [],
    ) {
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

    public function send(): never
    {
        http_response_code($this->status);
        foreach (array_merge([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ], $this->headers) as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
        exit;
    }
}
