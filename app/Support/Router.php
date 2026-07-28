<?php
declare(strict_types=1);
namespace App\Support;

final class Router {
    private array $routes = [];
    public function get(string $pattern, callable $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable $handler): void { $this->add('POST', $pattern, $handler); }
    private function add(string $method, string $pattern, callable $handler): void { $this->routes[$method][$pattern] = $handler; }
    public function dispatch(string $method, string $path): mixed {
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = '#^'.preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern).'$#';
            if (preg_match($regex, rtrim($path,'/') ?: '/', $matches)) return $handler(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
        }
        http_response_code(404); return View::render('errors/404', ['title'=>'Página não encontrada']);
    }
}
