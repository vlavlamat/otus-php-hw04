<?php

// Подключаем автозагрузчик Composer
require __DIR__ . '/../vendor/autoload.php';

// Подключаем классы из пространства имен App
use App\Validator;
use App\Router;
use App\StatsCollector;

// Устанавливаем заголовки для JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Добавим работу с сессиями
session_start(); // ← автоматически использует RedisCluster!

// Счётчик запросов в сессии
$_SESSION['request_count'] = ($_SESSION['request_count'] ?? 0) + 1;
$_SESSION['last_request'] = date('Y-m-d H:i:s');

// Обрабатываем preflight запросы
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$router = new Router();

// Маршрут для валидации скобок
$router->addRoute('POST', '/api/validate', function () {
    try {
        // Получаем JSON из тела запроса
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid JSON format',
                'error_code' => 'INVALID_JSON'
            ]);
            return;
        }

        if (!isset($data['string'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required parameter',
                'error_code' => 'MISSING_PARAMETER',
                'field' => 'string'
            ]);
            return;
        }

        $string = $data['string'];

        // Создаем экземпляр StatsCollector для сбора статистики
        $statsCollector = new StatsCollector();

        $isValid = Validator::validate($string);

        // Сохраняем статистику о валидации
        $statsCollector->incrementValidationCounter($string, $isValid);

        if ($isValid) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Valid bracket sequence',
                'valid' => true
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid bracket sequence',
                'error_code' => 'INVALID_SEQUENCE',
                'valid' => false
            ]);
        }

    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'error_code' => 'VALIDATION_ERROR',
            'valid' => false
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Internal server error',
            'error_code' => 'INTERNAL_ERROR'
        ]);
    }
});

// Маршрут для проверки статуса системы
$router->addRoute('GET', '/api/status', function () {
    try {
        $statsCollector = new StatsCollector();
        $redisConnected = $statsCollector->isConnected();

        echo json_encode([
            'status' => 'OK',
            'service' => 'bracket-validator',
            'version' => '1.0.0',
            'timestamp' => date('c'),
            'server' => gethostname(),
            'redis_cluster' => $redisConnected ? 'connected' : 'disconnected'
        ]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Internal server error',
            'error_code' => 'INTERNAL_ERROR',
            'redis_cluster' => 'disconnected'
        ]);
    }
});

// Диспетчеризация запроса
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
