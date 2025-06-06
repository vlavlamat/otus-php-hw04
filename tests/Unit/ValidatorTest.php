<?php

namespace Tests\Unit;

use App\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * Test that valid bracket strings are correctly validated.
     *
     * @dataProvider validBracketStringsProvider
     */
    public function testValidBracketStrings(string $input): void
    {
        $this->assertTrue(Validator::validate($input));
    }

    /**
     * Test that invalid bracket strings are correctly rejected.
     *
     * @dataProvider invalidBracketStringsProvider
     */
    public function testInvalidBracketStrings(string $input): void
    {
        $this->assertFalse(Validator::validate($input));
    }

    /**
     * Test that empty input throws an exception.
     */
    public function testEmptyInputThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty input.');
        
        Validator::validate('');
    }

    /**
     * Data provider for valid bracket strings.
     */
    public function validBracketStringsProvider(): array
    {
        return [
            'simple pair' => ['()'],
            'nested pairs' => ['(())'],
            'multiple pairs' => ['()()'],
            'complex valid string' => ['(())()(())'],
        ];
    }

    /**
     * Data provider for invalid bracket strings.
     */
    public function invalidBracketStringsProvider(): array
    {
        return [
            'unbalanced opening' => ['('],
            'unbalanced closing' => [')'],
            'wrong order' => [')('],
            'invalid characters' => ['(a)'],
            'unbalanced nested' => ['((())'],
        ];
    }
}