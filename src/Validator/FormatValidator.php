<?php

declare(strict_types=1);

namespace App\Validator;

use App\Models\ValidationResult;

/**
 * Валидатор формата строки скобок
 * Проверяет нормализацию, пустоту и наличие только допустимых символов
 */
final class FormatValidator
{
    private const MAX_LENGTH = 30;

    /**
     * Валидирует формат строки скобок
     *
     * @param string $brackets Строка для валидации
     * @return ValidationResult Результат валидации формата
     */
    public function validateFormat(string $brackets): ValidationResult
    {
        // Нормализуем строку
        $cleanBrackets = trim($brackets);

        // Проверяем на пустоту
        if ($cleanBrackets === '') {
            return ValidationResult::empty($cleanBrackets);
        }

        // Ограничение длины
        if (mb_strlen($cleanBrackets) > self::MAX_LENGTH) {
            return ValidationResult::invalidFormat($cleanBrackets);
        }

        // Проверяем, что строка содержит только скобки '(' и ')'
        if (!preg_match('/^[()]+$/', $cleanBrackets)) {
            return ValidationResult::invalidFormat($cleanBrackets);
        }

        // Формат корректный, возвращаем успешный результат с нормализованной строкой
        return ValidationResult::valid($cleanBrackets);
    }

    /**
     * Фабричный метод
     */
    public static function createDefault(): FormatValidator
    {
        return new self();
    }
}