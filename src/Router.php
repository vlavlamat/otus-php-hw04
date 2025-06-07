<?php

namespace App;

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
     * @var array
     */
    private array $routes = [];

    /**
     * Добавляет новый маршрут в маршрутизатор
     * 
     * @param string $method - HTTP-метод (GET, POST, PUT, DELETE и т.д.)
     * @param string $path - Путь URI для маршрута (например, '/api/validate')
     * @param callable $handler - Функция-обработчик, которая будет вызвана при совпадении маршрута
     * @return void
     */
    public function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method), // Преобразуем метод к верхнему регистру для единообразия
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
     * @param string $method - HTTP-метод запроса
     * @param string $uri - URI запроса
     * @return void
     */
    public function dispatch(string $method, string $uri): void
    {
        // Убираем query string из URI (часть после знака вопроса)
        $uri = parse_url($uri, PHP_URL_PATH);

        // Перебираем все зарегистрированные маршруты
        foreach ($this->routes as $route) {
            // Проверяем совпадение метода и пути
            if ($route['method'] === strtoupper($method) && $this->matchPath($route['path'], $uri)) {
                // Вызываем обработчик маршрута
                call_user_func($route['handler']);
                return; // Завершаем обработку после вызова обработчика
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
