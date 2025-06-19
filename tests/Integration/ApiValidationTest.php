<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Router;
use App\Validator;

class ApiValidationTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->setupValidationRoute();
    }

    /**
     * Настройка маршрута валидации как в реальном приложении
     */
    private function setupValidationRoute(): void
    {
        $this->router->addRoute('POST', '/validate', function () {
            try {
                // Для тестирования используем глобальную переменную вместо php://input
                global $testInputData;
                $data = $testInputData;

                if (!array_key_exists('string', $data)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Missing required parameter',
                        'error_code' => 'MISSING_PARAMETER',
                        'field' => 'string'
                    ]);
                    return;
                }

                $string = $data['string'];

                // Добавить валидацию
                if (!is_string($string)) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'String parameter must be a string',
                        'error_code' => 'INVALID_TYPE'
                    ]);
                    return;
                }

                if (strlen($string) > 10000) { // Ограничение длины
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'String too long (max 10000 characters)',
                        'error_code' => 'STRING_TOO_LONG'
                    ]);
                    return;
                }

                $isValid = Validator::validate($string);

                if ($isValid) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Valid bracket sequence',
                        'valid' => true
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid bracket sequence',
                        'error_code' => 'INVALID_SEQUENCE',
                        'valid' => false
                    ]);
                }

            } catch (\InvalidArgumentException $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'error_code' => 'VALIDATION_ERROR',
                    'valid' => false
                ]);
            } catch (\Throwable $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Internal server error',
                    'error_code' => 'INTERNAL_ERROR'
                ]);
            }
        });
    }

    /**
     * Тест валидации типа данных - строка передана как число
     */
    public function testValidationWithIntegerInput(): void
    {
        // Мокируем входные данные с числом вместо строки
        $this->mockPhpInput('{"string": 123}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('String parameter must be a string', $response['message']);
        $this->assertEquals('INVALID_TYPE', $response['error_code']);
    }

    /**
     * Тест валидации типа данных - строка передана как массив
     */
    public function testValidationWithArrayInput(): void
    {
        $this->mockPhpInput('{"string": ["(", ")"]}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('String parameter must be a string', $response['message']);
        $this->assertEquals('INVALID_TYPE', $response['error_code']);
    }

    /**
     * Тест валидации типа данных - строка передана как объект
     */
    public function testValidationWithObjectInput(): void
    {
        $this->mockPhpInput('{"string": {"brackets": "()"}}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('String parameter must be a string', $response['message']);
        $this->assertEquals('INVALID_TYPE', $response['error_code']);
    }

    /**
     * Тест валидации типа данных - строка передана как boolean
     */
    public function testValidationWithBooleanInput(): void
    {
        $this->mockPhpInput('{"string": true}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('String parameter must be a string', $response['message']);
        $this->assertEquals('INVALID_TYPE', $response['error_code']);
    }

    /**
     * Тест валидации типа данных - строка передана как null
     */
    public function testValidationWithNullInput(): void
    {
        $this->mockPhpInput('{"string": null}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('String parameter must be a string', $response['message']);
        $this->assertEquals('INVALID_TYPE', $response['error_code']);
    }

    /**
     * Тест валидации длины строки - строка слишком длинная
     */
    public function testValidationWithTooLongString(): void
    {
        // Создаем строку длиной 10001 символ
        $longString = str_repeat('(', 10001);
        $this->mockPhpInput('{"string": "' . $longString . '"}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('error', $response['status']);
        $this->assertEquals('String too long (max 10000 characters)', $response['message']);
        $this->assertEquals('STRING_TOO_LONG', $response['error_code']);
    }

    /**
     * Тест валидации длины строки - строка ровно 10000 символов (граничный случай)
     */
    public function testValidationWithExactly10000Characters(): void
    {
        // Создаем строку длиной ровно 10000 символов
        $maxString = str_repeat('(', 5000) . str_repeat(')', 5000);
        $this->mockPhpInput('{"string": "' . $maxString . '"}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // Строка должна пройти валидацию длины и дойти до валидации скобок
        $this->assertEquals('success', $response['status']);
        $this->assertTrue($response['valid']);
    }

    /**
     * Тест валидации длины строки - строка 9999 символов (в пределах лимита)
     */
    public function testValidationWith9999Characters(): void
    {
        // Создаем строку длиной 9999 символов
        $validString = str_repeat('(', 4999) . str_repeat(')', 4999) . '(';
        $this->mockPhpInput('{"string": "' . $validString . '"}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // Строка должна пройти валидацию длины, но не пройти валидацию скобок
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('INVALID_SEQUENCE', $response['error_code']);
        $this->assertFalse($response['valid']);
    }

    /**
     * Тест успешной валидации с корректной строкой
     */
    public function testValidationWithValidString(): void
    {
        $this->mockPhpInput('{"string": "()()"}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals('success', $response['status']);
        $this->assertTrue($response['valid']);
        $this->assertEquals('Valid bracket sequence', $response['message']);
    }

    /**
     * Тест комбинированной валидации - проверяем, что валидация типа выполняется перед валидацией длины
     */
    public function testValidationOrderTypeBeforeLength(): void
    {
        // Передаем число, которое если бы было строкой, было бы слишком длинным
        $this->mockPhpInput('{"string": 12345678901234567890}');

        ob_start();
        $this->router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // Должна сработать валидация типа, а не длины
        $this->assertEquals('error', $response['status']);
        $this->assertEquals('INVALID_TYPE', $response['error_code']);
        $this->assertEquals('String parameter must be a string', $response['message']);
    }

    /**
     * Мокирует php://input для тестирования
     */
    private function mockPhpInput(string $jsonData): void
    {
        // Устанавливаем глобальную переменную с декодированными данными
        global $testInputData;
        $testInputData = json_decode($jsonData, true);
    }
}
