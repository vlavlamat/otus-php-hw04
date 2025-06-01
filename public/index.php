<?php

// Подключаем автозагрузчик Composer
require __DIR__ . '/../vendor/autoload.php';

// Подключаем класс Validator из пространства имен App
use App\Validator;

// Устанавливаем заголовок ответа как JSON
header('Content-Type: application/json');

try {
    // Проверяем, что запрос пришёл методом POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // 405 Method Not Allowed
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    // Получаем параметр 'string' из POST-запроса
    $string = $_POST['string'] ?? null;

    // Если параметр отсутствует, возвращаем ошибку 400
    if ($string === null) {
        http_response_code(400); // 400 Bad Request
        echo json_encode(['error' => 'Missing "string" parameter']);
        exit;
    }

    // Проверяем строку с помощью валидатора
    if (Validator::validate($string)) {
        echo json_encode(['message' => 'OK']); // строка корректна
    } else {
        http_response_code(400); // 400 Bad Request
        echo json_encode(['message' => 'Bad request']); // строка некорректна
    }

// Ловим исключения валидации (например, пустая строка)
} catch (InvalidArgumentException $e) {
    http_response_code(400); // 400 Bad Request
    echo json_encode(['error' => $e->getMessage()]);

// Ловим все остальные ошибки
} catch (Throwable $e) {
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}
