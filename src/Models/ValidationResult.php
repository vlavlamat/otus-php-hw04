<?php

declare(strict_types=1);

namespace App\Models;

/**
 * DTO для результата валидации скобочных последовательностей
 *
 * Инкапсулирует результат валидации, включая исходную строку и статус проверки.
 * Предоставляет типизированные методы для создания различных типов результатов
 * и проверки состояния валидации.
 */
final class ValidationResult
{
    /**
     * Возможные статусы валидации
     */
    public const STATUS_VALID = 'valid';
    public const STATUS_INVALID = 'invalid';
    public const STATUS_EMPTY = 'empty';
    public const STATUS_INVALID_FORMAT = 'invalid_format';

    /**
     * @param string $brackets Строка скобок, которая была валидирована
     * @param string $status Статус валидации (valid, invalid_format, invalid_balance)
     */
    public function __construct(
        public readonly string $brackets,
        public readonly string $status
    )
    {
    }

    /**
     * Создает результат успешный валидации
     *
     * @param string $brackets Корректная строка скобок
     * @return ValidationResult Результат со статусом 'valid'
     */
    public static function valid(string $brackets): ValidationResult
    {
        return new self($brackets, self::STATUS_VALID);
    }

    /**
     * Создает результат для некорректной последовательности скобок
     *
     * Исполняется для ошибок баланса скобок (незакрытые или неправильный порядок)
     *
     * @param string $brackets Некорректная строка скобок
     * @return ValidationResult Результат со статусом 'invalid'
     */
    public static function invalid(string $brackets): ValidationResult
    {
        return new self($brackets, self::STATUS_INVALID);
    }

    /**
     * Создает результат для пустой строки
     *
     * @param string $brackets Пустая или содержащая только пробелы строка
     * @return ValidationResult Результат со статусом 'empty'
     */
    public static function empty(string $brackets): ValidationResult
    {
        return new self($brackets, self::STATUS_EMPTY);
    }

    /**
     * Создает результат для строки с недопустимыми символами
     *
     * Используется, когда строка содержит символы отличные от '(' и ')'
     *
     * @param string $brackets Строка с недопустимыми символами
     * @return ValidationResult Результат со статусом 'invalid_format'
     */
    public static function invalidFormat(string $brackets): ValidationResult
    {
        return new self($brackets, self::STATUS_INVALID_FORMAT);
    }

    /**
     * Проверяет, прошла ли валидация успешно
     *
     * @return bool true если валидация успешна, false в противном случае
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_VALID;
    }

    /**
     * Проверяет, связана ли ошибка с пустой строкой
     *
     * @return bool true если строка пустая, false в противной случае
     */
    public function isEmpty(): bool
    {
        return $this->status === self::STATUS_EMPTY;
    }

    /**
     * Проверяет, связана ли ошибка с неверным форматом
     *
     * @return bool true если строка имеет неверный формат - недопустимые символы
     */
    public function isInvalidFormat(): bool
    {
        return $this->status === self::STATUS_INVALID_FORMAT;
    }
}