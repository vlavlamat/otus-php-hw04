<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

/**
 * Класс для формирования JSON ответов
 *
 * Инкапсулирует всю логику формирования JSON ответов.
 */
class JsonResponse
{
    private array $data;
    private int $statusCode;
    private array $headers;

    /**
     * @param array $data Данные для отправки
     * @param int $statusCode HTTP статус код
     * @param array $headers Дополнительные заголовки
     */
    public function __construct(array $data, int $statusCode = 200, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Кодирует данные ответа в JSON.
     *
     * @return string
     * @throws JsonException
     */
    public function toJson(): string
    {
        return json_encode(
            $this->data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    /**
     * Создает успешный ответ
     *
     * @param array $data
     * @return static
     */
    public static function success(array $data): static
    {
        return new static($data, 200);
    }

    /**
     * Создает неуспешный ответ
     *
     * @param array $data
     * @return static
     */
    public static function failed(array $data): static
    {
        return new static($data, 400);
    }

    /**
     * Создает ответ с ошибкой
     *
     * @param string $message
     * @param int $statusCode
     * @return static
     */
    public static function error(string $message, int $statusCode = 400): static
    {
        return new static([
            'error' => ['message' => $message]
        ], $statusCode);
    }
}