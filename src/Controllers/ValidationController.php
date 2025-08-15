<?php

declare(strict_types=1);

namespace App\Controllers;


use JsonException;
use App\Http\JsonResponse;
use App\Http\ResponseSender;
use App\Services\ValidationService;

/**
 * Контроллер обработки запроса валидации скобочных последовательностей
 * Принимает JSON, делегирует логику сервису, возвращает стандартизированный JSON-ответ.
 */
class ValidationController
{
    private ValidationService $validationService;

    public function __construct(ValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Обрабатывает запрос валидации.
     *
     * @return void
     * @throws JsonException Если тело запроса содержит некорректный JSON.
     */
    public function handleValidationRequest(): void
    {
        $requestData = $this->getRequestData();

        // Проверяем наличие и тип поля string
        if (!isset($requestData['string']) || !is_string($requestData['string'])) {
            $response = JsonResponse::error('Поле "string" обязательно и должно быть строкой');
            ResponseSender::sendJson($response);
            return;
        }

        // Валидируем строку через сервис (передаем "сырую" строку)
        $result = $this->validationService->validateString($requestData['string']);

        if ($result->isValid()) {
            $response = JsonResponse::success(['status' => 'valid']);
            ResponseSender::sendJson($response);
            return;
        } elseif ($result->isEmpty()) {
            $response = JsonResponse::failed(['status' => 'empty']);
            ResponseSender::sendJson($response);
            return;
        } elseif ($result->isInvalidFormat()) {
            $response = JsonResponse::failed(['status' => 'invalid_format']);
            ResponseSender::sendJson($response);
            return;
        }

        // Любой другой неуспешный результат трактуется как invalid (несбалансированные скобки)
        ResponseSender::sendJson(JsonResponse::failed(['status' => 'invalid']));
    }

    /**
     * Получает и парсит данные из тела HTTP запроса
     *
     * @throws JsonException
     */
    private function getRequestData(): array
    {
        // Читаем сырые данные из входящего запроса
        $input = file_get_contents('php://input');

        // Возвращаем пустой массив для пустых запросов
        if ($input === '' || $input === false) {
            return [];
        }

        // Парсим JSON с включенным исключением при ошибках
        $data = json_decode($input, true, 4, JSON_THROW_ON_ERROR);

        // Гарантируем, что возвращаем массив (на случай, если JSON содержит не объект)
        return is_array($data) ? $data : [];
    }

    /**
     * Фабричный метод для создания контроллера с зависимостями по умолчанию
     */
    public static function createDefault(): ValidationController
    {
        return new self(
            ValidationService::createDefault(),
        );
    }
}