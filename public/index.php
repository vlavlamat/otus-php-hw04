<?php

declare(strict_types=1);

// Подключаем автозагрузчик Composer
require __DIR__ . '/../vendor/autoload.php';

// Подключаем классы из пространства имен App
use App\Bootstrap\EnvironmentLoader;
use App\Controllers\RedisHealthController;
use App\Controllers\ValidationController;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Router;

// Валидация переменных окружения (Infrastructure слой)
EnvironmentLoader::load();

// Инициализация CORS middleware (HTTP слой)
$corsMiddleware = new CorsMiddleware();

// Обработка preflight запросов
if ($corsMiddleware->isPreflight()) {
    $corsMiddleware->handlePreflight();
}

// Установка CORS заголовков для всех запросов
$corsMiddleware->handle();

// Инициализация сессий с Redis Cluster
session_start();

$router = new Router();
$validationController = ValidationController::createDefault();
$healthController = RedisHealthController::createDefault();

// Регистрация маршрутов
$router->addRoute('POST', '/validate', [$validationController, 'validate']);
$router->addRoute('GET', '/status', [$healthController, 'getStatus']);

// Диспетчеризация запроса
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Запускаем маршрутизацию и обработку запроса
$router->dispatch($method, $uri);
