<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

/**
 * Класс Validator
 * 
 * Предоставляет функциональность для проверки корректности скобочных последовательностей.
 * Скобочная последовательность считается корректной, если:
 * 1. Каждой открывающей скобке соответствует закрывающая
 * 2. Закрывающая скобка не может идти раньше соответствующей ей открывающей
 * 3. Строка содержит только скобки '(' и ')'
 */
class Validator
{
    /**
     * Проверяет корректность скобочной последовательности
     * 
     * Метод анализирует входную строку и определяет, является ли она
     * корректной скобочной последовательностью. Используется алгоритм
     * подсчета баланса открытых и закрытых скобок.
     * 
     * @param string $input - Строка для проверки
     * @return bool - true, если строка является корректной скобочной последовательностью, иначе false
     * @throws InvalidArgumentException - Если входная строка пуста
     */
    public static function validate(string $input): bool
    {
        // Проверяем, что строка не пустая
        if (empty($input)) {
            throw new InvalidArgumentException('Empty input.');
        }

        $balance = 0; // счётчик открытых скобок
        $length = strlen($input); // длина строки

        // Проходим по каждому символу строки
        for ($i = 0; $i < $length; $i++) {
            if ($input[$i] === '(') {
                // Открывающая скобка увеличивает баланс
                $balance++; 
            } elseif ($input[$i] === ')') {
                // Закрывающая скобка уменьшает баланс
                $balance--; 

                // Если баланс стал отрицательным, значит закрывающая скобка
                // встретилась раньше соответствующей открывающей - это ошибка
                if ($balance < 0) {
                    return false; // если баланс ушёл в минус — ошибка (больше закрывающих скобок)
                }
            } else {
                // Если встретился любой символ кроме скобок - это ошибка
                // Валидная строка должна содержать только скобки
                return false; // если встречен любой другой символ — ошибка
            }
        }

        // Если после всех проверок баланс равен 0, значит каждой открывающей
        // скобке соответствует закрывающая, и строка корректна
        return $balance === 0;
    }
}
