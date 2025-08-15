<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;
use JsonException;
use RuntimeException;
use App\Http\JsonResponse;
use App\Http\ResponseSender;
use InvalidArgumentException;

/**
 * Простой централизованный обработчик исключений
 */
class ExceptionHandler
{
    /**
     * Центральная точка обработки необработанных исключений
     *
     * Создает JSON-ответ на основе типа исключения и отправляет его клиенту.
     */
    public function handleException(Throwable $exception): void
    {
        try {
            $response = $this->createResponse($exception);
            ResponseSender::sendJson($response);
        } catch (JsonException) {
            // Если JSON отправить нельзя, используем простой резервный ответ
            $this->sendFallbackResponse();
        }
    }

    /**
     * Строит JsonResponse для данного исключения
     */
    private function createResponse(Throwable $exception): JsonResponse
    {
        if ($exception instanceof InvalidArgumentException) {
            return JsonResponse::error($exception->getMessage());
        }

        if ($exception instanceof RuntimeException) {
            $code = $exception->getCode() ?: 404;
            return JsonResponse::error($exception->getMessage(), $code);
        }

        if ($exception instanceof JsonException) {
            return JsonResponse::error('Некорректный JSON в запросе');
        }

        // Остальные ошибки: логируем и возвращаем 500
        error_log("Unexpected error: " . $exception->getMessage());
        return JsonResponse::error('Внутренняя ошибка сервера', 500);
    }

    /**
     * Минимальный резервный ответ, если отправка JSON невозможна
     */
    private function sendFallbackResponse(): void
    {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo '{"error": {"message": "Критическая ошибка сервера"}}';

    }
}