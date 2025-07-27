<?php

namespace Tests\Unit;

use App\Validator\BracketValidator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * Тест проверяет, что валидные скобочные строки корректно проходят валидацию.
     *
     * @dataProvider validBracketStringsProvider
     */
    public function testValidBracketStrings(string $input): void
    {
        $this->assertTrue(BracketValidator::validate($input));
    }

    /**
     * Тест проверяет, что невалидные скобочные строки корректно отклоняются.
     *
     * @dataProvider invalidBracketStringsProvider
     */
    public function testInvalidBracketStrings(string $input): void
    {
        $this->assertFalse(BracketValidator::validate($input));
    }

    /**
     * Тест проверяет, что пустой ввод вызывает исключение.
     */
    public function testEmptyInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty input.');

        BracketValidator::validate('');
    }

    /**
     * Поставщик данных для валидных скобочных строк.
     */
    public function validBracketStringsProvider(): array
    {
        return [
            'простая пара' => ['()'],
            'вложенные пары' => ['(())'],
            'множественные пары' => ['()()'],
            'сложная валидная строка' => ['(())()(())'],
        ];
    }

    /**
     * Поставщик данных для невалидных скобочных строк.
     */
    public function invalidBracketStringsProvider(): array
    {
        return [
            'несбалансированная открывающая' => ['('],
            'несбалансированная закрывающая' => [')'],
            'неправильный порядок' => [')('],
            'недопустимые символы' => ['(a)'],
            'несбалансированная вложенная' => ['((())'],
        ];
    }
}
