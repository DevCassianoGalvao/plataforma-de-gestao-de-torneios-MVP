<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$method][] = [$path, $handler];
    }

    public function dispatch(Request $request): Response
    {
        $path = Config::stripBasePath($request->path);
        foreach ($this->routes[$request->method] ?? [] as [$pattern, $handler]) {
            $segments = explode('/', trim($pattern, '/'));
            $regexParts = [];
            foreach ($segments as $segment) {
                $segmentRegex = '';
                foreach (preg_split('/(\{[^}]+\})/', $segment, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
                    $segmentRegex .= preg_match('/^\{[^}]+\}$/', $part) === 1 ? '([^/]+)' : preg_quote($part, '#');
                }
                $regexParts[] = $segmentRegex;
            }
            $regex = '/' . implode('/', $regexParts);
            if ($regex !== null && preg_match('#^' . rtrim($regex, '/') . '/?$#', $path, $matches) === 1) {
                array_shift($matches);
                return $handler($request, $matches);
            }
        }

        return Response::html(View::render('errors/404', ['path' => $path]), 404);
    }
}
