<?php

namespace App;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Убираем query string из URI
        $uri = parse_url($uri, PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && $this->matchPath($route['path'], $uri)) {
                call_user_func($route['handler']);
                return;
            }
        }

        // Если маршрут не найден
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }

    private function matchPath(string $pattern, string $uri): bool
    {
        // Простое сравнение путей (можно расширить для параметров)
        return $pattern === $uri;
    }
}