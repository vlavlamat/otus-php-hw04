<?php

namespace App;

class Validator
{
    // Статический метод для проверки скобочной строки
    public static function validate(string $input): bool
    {
        // Проверяем, что строка не пустая
        if (empty($input)) {
            throw new \InvalidArgumentException('Empty input.');
        }

        $balance = 0; // счётчик открытых скобок
        $length = strlen($input); // длина строки

        // Проходим по каждому символу строки
        for ($i = 0; $i < $length; $i++) {
            if ($input[$i] === '(') {
                $balance++; // открывающая скобка увеличивает баланс
            } elseif ($input[$i] === ')') {
                $balance--; // закрывающая скобка уменьшает баланс
                if ($balance < 0) {
                    return false; // если баланс ушёл в минус — ошибка (больше закрывающих скобок)
                }
            } else {
                return false; // если встречен любой другой символ — ошибка
            }
        }

        // Если после всех проверок баланс равен 0 — строка корректна
        return $balance === 0;
    }
}
