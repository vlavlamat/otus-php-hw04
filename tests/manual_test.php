<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Validator\BracketValidator;

// Тестовые случаи
$testCases = [
    // Валидные скобочные строки
    ['()' => true],
    ['(())' => true],
    ['()()' => true],
    ['(())()(())' => true],

    // Невалидные скобочные строки
    ['(' => false],
    [')' => false],
    [')(' => false],
    ['(a)' => false],
    ['((())' => false],
];

// Запуск тестов
$passed = 0;
$failed = 0;

echo "Запуск тестов для класса BracketValidator...\n\n";

foreach ($testCases as $testCase) {
    foreach ($testCase as $input => $expected) {
        echo "Тестирование ввода: '$input'\n";
        echo "Ожидаемый результат: " . ($expected ? 'true' : 'false') . "\n";

        try {
            $result = BracketValidator::validate($input);
            echo "Фактический результат: " . ($result ? 'true' : 'false') . "\n";

            if ($result === $expected) {
                echo "✅ Тест ПРОЙДЕН\n";
                $passed++;
            } else {
                echo "❌ Тест НЕ ПРОЙДЕН\n";
                $failed++;
            }
        } catch (\Exception $e) {
            echo "Исключение: " . $e->getMessage() . "\n";
            if ($input === '' && $e instanceof \InvalidArgumentException) {
                echo "✅ Тест ПРОЙДЕН (Ожидаемое исключение для пустого ввода)\n";
                $passed++;
            } else {
                echo "❌ Тест НЕ ПРОЙДЕН (Неожиданное исключение)\n";
                $failed++;
            }
        }

        echo "\n";
    }
}

// Тест пустого ввода
echo "Тестирование пустого ввода\n";
echo "Ожидаемый результат: Исключение с сообщением 'Empty input.'\n";

try {
    BracketValidator::validate('');
    echo "❌ Тест НЕ ПРОЙДЕН (Исключение не выброшено)\n";
    $failed++;
} catch (\InvalidArgumentException $e) {
    echo "Исключение: " . $e->getMessage() . "\n";
    if ($e->getMessage() === 'Empty input.') {
        echo "✅ Тест ПРОЙДЕН (Ожидаемое исключение для пустого ввода)\n";
        $passed++;
    } else {
        echo "❌ Тест НЕ ПРОЙДЕН (Неожиданное сообщение исключения)\n";
        $failed++;
    }
} catch (\Exception $e) {
    echo "Исключение: " . $e->getMessage() . "\n";
    echo "❌ Тест НЕ ПРОЙДЕН (Неожиданный тип исключения)\n";
    $failed++;
}

echo "\n";
echo "Итоги тестирования:\n";
echo "✅ Пройдено: $passed\n";
echo "❌ Не пройдено: $failed\n";
echo "Всего: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n🎉 Все тесты пройдены!\n";
    exit(0);
} else {
    echo "\n❌ Некоторые тесты не пройдены.\n";
    exit(1);
}
