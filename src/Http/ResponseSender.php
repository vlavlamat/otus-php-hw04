<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;


/**
 * Класс для отправки HTTP ответов
 *
 * Отвечает только за HTTP отправку
 */
class ResponseSender
{
    /**
     * Отправляет JSON ответ клиенту
     *
     * @param JsonResponse $response
     * @return void
     * @throws JsonException
     */
    public static function sendJson(JsonResponse $response): void
    {
        // HTTP Status
        http_response_code($response->getStatusCode());

        // Базовые заголовки
        header('Content-Type: application/json; charset=utf-8');

        // Дополнительные заголовки
        foreach ($response->getHeaders() as $name => $value) {
            header("$name: $value");
        }

        // Отправка JSON
        echo $response->toJson();
    }
}