<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Класс HTTP-ответа для CORS preflight запросов
 *
 * Предоставляет специализированную реализацию HTTP-ответа для обработки
 * CORS preflight запросов. Инкапсулирует содержимое ответа, код статуса
 * и заголовки с возможностью отправки клиенту и доступа к данным для тестирования.
 */
class PreflightResponse
{
    /** @var string Содержимое HTTP-ответа */
    private string $content;

    /** @var int HTTP код статуса ответа */
    private int $statusCode;

    /** @var array<string, string> Ассоциативный массив HTTP заголовков */
    private array $headers;

    /**
     * Создает новый экземпляр CORS preflight ответа
     *
     * @param string $content Содержимое ответа
     * @param int $statusCode HTTP код статуса
     * @param array<string, string> $headers Ассоциативный массив заголовков
     */
    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Отправляет HTTP-ответ клиенту
     *
     * Устанавливает код статуса, отправляет заголовки и выводит содержимое ответа.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->content;
    }
}