<?php
declare(strict_types=1);

// Подключаем автозагрузчик Composer
require __DIR__ . '/../vendor/autoload.php';

// Подключаем классы из пространства имен App
use App\Validator;
use App\Router;
use App\RedisHealthChecker;

// Устанавливаем заголовки для JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обрабатываем preflight запросы
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Добавим работу с сессиями
session_start(); // ← автоматически использует RedisCluster!

$router = new Router();

// Маршрут для валидации скобок
$router->addRoute('POST', '/validate', function () {
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

        if (!array_key_exists('string', $data)) {
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

        // Добавить валидацию
        if (!is_string($string)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'String parameter must be a string',
                'error_code' => 'INVALID_TYPE'
            ]);
            return;
        }

        if (strlen($string) > 10000) { // Ограничение длины
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'String too long (max 10000 characters)',
                'error_code' => 'STRING_TOO_LONG'
            ]);
            return;
        }

        $isValid = Validator::validate($string);

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
$router->addRoute('GET', '/status', function () {
    try {
        $redisChecker = new RedisHealthChecker();
        $redisConnected = $redisChecker->isConnected();
        $clusterStatus = $redisChecker->getClusterStatus();

        // Подсчитываем статистику узлов
        $connectedCount = 0;
        $totalNodes = count($clusterStatus);
        foreach ($clusterStatus as $nodeStatus) {
            if ($nodeStatus === 'connected') {
                $connectedCount++;
            }
        }

        // Получаем требуемый кворум из конфигурации
        $requiredQuorum = $redisChecker->getRequiredQuorum();

        echo json_encode([
            'status' => 'OK',
            'service' => 'bracket-validator',
            'version' => '1.0.0',
            'timestamp' => date('c'),
            'server' => gethostname(),
            'redis_cluster' => $redisConnected ? 'connected' : 'disconnected',
            'redis_details' => [
                'cluster_status' => $redisConnected ? 'healthy' : 'unhealthy',
                'connected_nodes' => $connectedCount,
                'total_nodes' => $totalNodes,
                'quorum_required' => $requiredQuorum,
                'quorum_met' => $connectedCount >= $requiredQuorum,
                'nodes' => $clusterStatus
            ]
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Internal server error',
            'error_code' => 'INTERNAL_ERROR',
            'redis_cluster' => 'disconnected',
            'redis_details' => [
                'cluster_status' => 'error',
                'error' => $e->getMessage()
            ]
        ]);
    }
});

// Диспетчеризация запроса
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
