<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Validator;

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    $string = $_POST['string'] ?? null;

    if ($string === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing "string" parameter']);
        exit;
    }

    if (Validator::validate($string)) {
        echo json_encode(['message' => 'OK']);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Bad request']);
    }

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
