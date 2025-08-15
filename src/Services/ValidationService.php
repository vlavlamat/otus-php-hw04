<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ValidationResult;
use App\Validator\FormatValidator;
use App\Validator\BracketValidator;

/**
 * Сервис валидации скобок
 * Оркестрирует процесс валидации: формат → баланс скобок
 */
class ValidationService
{
    private FormatValidator $formatValidator;
    private BracketValidator $bracketValidator;

    public function __construct(FormatValidator $formatValidator, BracketValidator $bracketValidator)
    {
        $this->formatValidator = $formatValidator;
        $this->bracketValidator = $bracketValidator;
    }

    /**
     * Валидирует строку скобок
     *
     * @param string $brackets Входная строка
     * @return ValidationResult Результат валидации
     */
    public function validateString(string $brackets): ValidationResult
    {
        // Шаг 1: Валидация формата
        $formatResult = $this->formatValidator->validateFormat($brackets);

        // Если формат некорректный, возвращаем результат валидации формата
        if (!$formatResult->isValid()) {
            return $formatResult;
        }

        // Шаг 2: Валидация баланса скобок (передаем нормализованную строку)
        return $this->bracketValidator->validateBrackets($formatResult->brackets);
    }

    /**
     * Фабричный метод для создания сервиса с валидатором по умолчанию
     */
    public static function createDefault(): ValidationService
    {
        return new self(
            FormatValidator::createDefault(),
            BracketValidator::createDefault()
        );
    }
}