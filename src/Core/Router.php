<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use InvalidArgumentException;

/**
 * Простой HTTP-маршрутизатор
 *
 * Обрабатывает HTTP-запросы, сопоставляя их с зарегистрированными маршрутами
 * и вызывая соответствующие обработчики на основе метода и пути.
 */
class Router
{
    /**
     * Зарегистрированные маршруты
     *
     * @var array<int, array{method: string, path: string, handler: callable}>
     */
    private array $routes = [];

    /**
     * Регистрирует новый маршрут
     *
     * @param string $method HTTP-метод (GET, POST, PUT, DELETE)
     * @param string $path Путь URI (например, '/api/users')
     * @param callable $handler Функция-обработчик
     *
     * @throws InvalidArgumentException Если путь имеет неверный формат
     * @throws InvalidArgumentException Если маршрут уже существует
     */
    public function addRoute(string $method, string $path, callable $handler): void
    {
        // Преобразуем метод к верхнему регистру для единообразия
        $method = strtoupper($method);

        // Валидация пути
        if (!$this->isValidPath($path)) {
            throw new InvalidArgumentException("Недопустимый путь $path");
        }

        // Проверяем дублирование
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                throw new InvalidArgumentException("Маршрут $method $path уже существует");
            }
        }
        // Создаем ассоциативный массив с данными маршрута и добавляем его в конец массива $this->routes
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    /**
     * Обрабатывает запрос и вызывает соответствующий обработчик
     *
     * @param string $method HTTP-метод запроса
     * @param string $uri URI запроса (query string удаляется)
     *
     * @throws InvalidArgumentException Если URI некорректен
     * @throws RuntimeException Если маршрут не найден (код 404)
     *
     * @example $router->dispatch('GET', '/api/users')
     * @example $router->dispatch('POST', '/api/validate?foo=bar') // найдет POST /api/validate
     */
    public function dispatch(string $method, string $uri): void
    {
        // Преобразуем метод к верхнему регистру для единообразия
        $method = strtoupper($method);

        // Убираем query string из URI
        $uri = parse_url($uri, PHP_URL_PATH);

        // Проверяем корректность URI
        if ($uri === false || $uri === null) {
            throw new InvalidArgumentException('Недопустимый URI');
        }

        // Проверяем корректность пути
        if (!$this->isValidPath($uri)) {
            throw new InvalidArgumentException('Недопустимый путь');
        }

        // Перебираем все зарегистрированные маршруты
        foreach ($this->routes as $route) {
            // Проверяем совпадение метода и пути
            if ($route['method'] === $method && $route['path'] === $uri) {
                // Вызываем обработчик маршрута
                $route['handler']();
                return;
            }
        }
        // Если маршрут не найден, возвращаем ошибку 404
        throw new RuntimeException('Маршрут не найден', 404);
    }

    /**
     * Проверяет корректность пути URI
     *
     * @param string $path Проверяемый путь
     * @return bool Результат проверки
     */
    private function isValidPath(string $path): bool
    {
        // Проверяем длину - RFC рекомендует ограничение
        if ($path === '' || strlen($path) > 2048) {
            return false;
        }

        // Проверяем, чтобы начиналось с '/'
        if ($path[0] !== '/') {
            return false;
        }

        // Запретить directory traversal (../)
        if (str_contains($path, '..')) {
            return false;
        }

        // Единственная проверка формата - позитивная валидация
        // Разрешаем только нужные символы
        return preg_match('/^\/[a-zA-Z0-9_\-.%\/]+$/', $path) === 1;
    }
}