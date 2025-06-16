<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use Throwable;

/**
 * Класс Router
 *
 * Простой маршрутизатор для обработки HTTP-запросов.
 * Позволяет регистрировать маршруты и перенаправлять запросы
 * к соответствующим обработчикам на основе метода HTTP и пути URI.
 */
class Router
{
    /**
     * Массив зарегистрированных маршрутов
     *
     * Каждый маршрут представлен ассоциативным массивом с ключами:
     * - method: HTTP-метод (GET, POST, и т.д.)
     * - path: путь URI
     * - handler: функция-обработчик
     *
     * @var array<int, array{method: string, path: string, handler: callable}>
     */
    private array $routes = [];

    /**
     * Добавляет новый маршрут в маршрутизатор
     *
     * @param string $method HTTP-метод (GET, POST, PUT, DELETE и т.д.)
     * @param string $path Путь URI для маршрута (например, '/api/validate')
     * @param callable $handler Функция-обработчик, которая будет вызвана при совпадении маршрута
     * @return void
     * @throws InvalidArgumentException Если маршрут с таким методом и путём уже существует
     */
    public function addRoute(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);

        // Проверяем дублирование
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                throw new InvalidArgumentException("Route $method $path already exists");
            }
        }
        $this->routes[] = [
            'method' => $method, // Преобразуем метод к верхнему регистру для единообразия
            'path' => $path,                 // Путь URI
            'handler' => $handler            // Функция-обработчик
        ];
    }

    /**
     * Обрабатывает входящий запрос и вызывает соответствующий обработчик
     *
     * Метод ищет маршрут, соответствующий HTTP-методу и URI запроса.
     * Если маршрут найден, вызывает его обработчик.
     * Если маршрут не найден, возвращает ответ 404 Not Found.
     *
     * @param string $method HTTP-метод запроса
     * @param string $uri URI запроса (query string будет удалён автоматически)
     * @return void
     *
     * @example
     *  $router->dispatch('GET', '/api/users'); // вызовет обработчик для GET /api/users
     *  $router->dispatch('POST', '/api/validate?foo=bar'); // уберёт query string и найдёт POST /api/validate
     */
    public function dispatch(string $method, string $uri): void
    {
        // Убираем query string из URI (часть после знака вопроса)
        $uri = parse_url($uri, PHP_URL_PATH);

        // Проверяем корректность URI
        if ($uri === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid URI']);
            return;
        }

        // Перебираем все зарегистрированные маршруты
        foreach ($this->routes as $route) {
            // Проверяем совпадение метода и пути
            if ($route['method'] === strtoupper($method) && $this->matchPath($route['path'], $uri)) {
                // Вызываем обработчик маршрута
                try {
                    call_user_func($route['handler']);
                } catch (Throwable $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Internal server error']);
                }
                return;
            }
        }

        // Если маршрут не найден, возвращаем ошибку 404
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
    }

    /**
     * Проверяет соответствие пути URI шаблону маршрута
     *
     * В текущей реализации выполняется простое сравнение строк.
     * Этот метод можно расширить для поддержки параметров в URL,
     * регулярных выражений или других способов сопоставления путей.
     *
     * @param string $pattern - Шаблон пути из зарегистрированного маршрута
     * @param string $uri - Фактический путь URI из запроса
     * @return bool - true, если путь соответствует шаблону, иначе false
     */
    private function matchPath(string $pattern, string $uri): bool
    {
        // Простое сравнение путей (можно расширить для параметров)
        return $pattern === $uri;
    }
}