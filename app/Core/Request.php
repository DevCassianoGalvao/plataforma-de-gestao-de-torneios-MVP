<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $body = [],
        public readonly array $headers = [],
    ) {
    }

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $parts = parse_url($uri) ?: [];
        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            (string) ($parts['path'] ?? '/'),
            $_GET,
            $_POST,
            self::headers(),
        );
    }

    public static function fake(string $method, string $path, array $body = []): self
    {
        return new self(strtoupper($method), $path, [], $body);
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    public function userAgent(): string
    {
        return substr($this->headers['USER-AGENT'] ?? (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    }

    private static function headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            }
        }
        return $headers;
    }
}
