<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;
use App\Http\Middleware\CorsMiddleware;
use App\Controllers\ValidationController;
use App\Controllers\RedisHealthController;

/**
 * Основной класс приложения
 *
 * Инициализирует компоненты приложения, обрабатывает входящие HTTP-запросы
 * и предоставляет централизованную обработку ошибок.
 */
class App
{
    private Router $router;
    private CorsMiddleware $corsMiddleware;
    private ExceptionHandler $exceptionHandler;

    public function __construct()
    {
        $this->router = new Router();
        $this->corsMiddleware = new CorsMiddleware();
        $this->exceptionHandler = new ExceptionHandler();
        $this->setupRoutes();
    }

    /**
     * Запускает приложение
     *
     * Обрабатывает CORS, инициализирует сессии и передает управление роутеру.
     * В случае критических ошибок выполняет централизованную обработку исключений.
     */
    public function run(): void
    {
        try {
            // Обработка CORS preflight
            if ($this->corsMiddleware->isPreflight()) {
                $response = $this->corsMiddleware->handlePreflight();
                $response->send();
                return;
            }

            // Установка CORS заголовков
            $this->corsMiddleware->handleHeaders();

            // Старт сессии Redis Cluster
            session_start();

            // Обработка запроса
            $method = $_SERVER['REQUEST_METHOD'];
            $uri = $_SERVER['REQUEST_URI'];

            $this->router->dispatch($method, $uri);

        } catch (Throwable $exception) {
            // Вся обработка исключений делегирована ExceptionHandler
            $this->exceptionHandler->handleException($exception);
        }
    }

    /**
     * Регистрирует маршруты приложения
     */
    private function setupRoutes(): void
    {
        $validationController = ValidationController::createDefault();
        $healthController = RedisHealthController::createDefault();

        $this->router->addRoute('POST', '/validate', [$validationController, 'handleValidationRequest']);
        $this->router->addRoute('GET', '/status', [$healthController, 'getStatus']);
    }
}