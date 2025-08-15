<?php

declare(strict_types=1);

namespace App\Validator;

use App\Models\ValidationResult;

/**
 * Валидатор баланса скобок
 * Проверяет корректность последовательности скобок
 */
class BracketValidator
{
    /**
     * Валидирует баланс скобок
     *
     * @param string $brackets - Нормализированная строка скобок (только '(' и ')')
     * @return ValidationResult - Результат валидации с статусом и причиной
     */
    public function validateBrackets(string $brackets): ValidationResult
    {
        $balance = 0;
        $length = strlen($brackets);

        // Однопроходный подсчет баланса скобок
        for ($i = 0; $i < $length; $i++) {
            if ($brackets[$i] === '(') {
                $balance++;
            } elseif ($brackets[$i] === ')') {
                $balance--;
                // Ранняя ошибка: закрывающая скобка без пары
                if ($balance < 0) {
                    return ValidationResult::invalid($brackets);
                }
            }
        }

        // Корректно только если баланс равен нулю
        if ($balance === 0) {
            return ValidationResult::valid($brackets);
        }
        return ValidationResult::invalid($brackets);
    }

    /**
     * Фабричный метод для создания BracketValidator с настройками по умолчанию
     */
    public static function createDefault(): BracketValidator
    {
        return new self();
    }
}
