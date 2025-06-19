<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Router;
use App\Validator;

class ComponentIntegrationTest extends TestCase
{
    /**
     * Комплексный тест интеграции Router и Validator
     * 
     * Объединяет функциональность из нескольких тестов:
     * - Базовую валидацию скобочных строк
     * - Обработку исключений
     * - Проверку различных типов строк (валидные/невалидные)
     */
    public function testRouterValidatorIntegration(): void
    {
        $router = new Router();

        // Добавляем маршрут для комплексной валидации
        $router->addRoute('POST', '/validate', function () {
            // Набор тестовых строк, включающий различные сценарии
            $testData = [
                // Валидные строки
                '()',
                '(())',
                '()()',
                '(())()(())',
                // Невалидные строки
                '((',
                '))',
                '()())',
                '(((',
                ')(', 
                '(a)',
                // Пустая строка для проверки исключения
                ''
            ];

            $results = [];
            foreach ($testData as $string) {
                try {
                    $isValid = Validator::validate($string);
                    $results[] = [
                        'input' => $string,
                        'valid' => $isValid,
                        'error' => null
                    ];
                } catch (\InvalidArgumentException $e) {
                    $results[] = [
                        'input' => $string,
                        'valid' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            echo json_encode([
                'total' => count($testData),
                'results' => $results
            ]);
        });

        // Выполняем запрос
        ob_start();
        $router->dispatch('POST', '/validate');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        // Проверяем общие результаты
        $this->assertIsArray($response);
        $this->assertEquals(11, $response['total']);
        $this->assertCount(11, $response['results']);

        // Проверяем валидные строки
        $this->assertTrue($response['results'][0]['valid']); // '()'
        $this->assertTrue($response['results'][1]['valid']); // '(())'
        $this->assertTrue($response['results'][2]['valid']); // '()()'
        $this->assertTrue($response['results'][3]['valid']); // '(())()(())'

        // Проверяем невалидные строки
        $this->assertFalse($response['results'][4]['valid']); // '(('
        $this->assertFalse($response['results'][5]['valid']); // '))'
        $this->assertFalse($response['results'][6]['valid']); // '()())'
        $this->assertFalse($response['results'][7]['valid']); // '((('
        $this->assertFalse($response['results'][8]['valid']); // ')('
        $this->assertFalse($response['results'][9]['valid']); // '(a)'

        // Проверяем обработку исключения для пустой строки
        $this->assertFalse($response['results'][10]['valid']); // ''
        $this->assertEquals('Empty input.', $response['results'][10]['error']);
    }

    /**
     * Тест интеграции Router с различными HTTP методами и Validator
     * 
     * Проверяет, что Router правильно обрабатывает разные HTTP методы
     * при интеграции с Validator
     */
    public function testRouterHttpMethodsWithValidator(): void
    {
        $router = new Router();
        $testString = '()()';

        // Добавляем маршруты для разных методов
        $router->addRoute('GET', '/test', function () use ($testString) {
            $isValid = Validator::validate($testString);
            echo json_encode(['method' => 'GET', 'valid' => $isValid]);
        });

        $router->addRoute('POST', '/test', function () use ($testString) {
            $isValid = Validator::validate($testString);
            echo json_encode(['method' => 'POST', 'valid' => $isValid]);
        });

        $router->addRoute('PUT', '/test', function () use ($testString) {
            $isValid = Validator::validate($testString);
            echo json_encode(['method' => 'PUT', 'valid' => $isValid]);
        });

        // Тестируем каждый метод
        ob_start();
        $router->dispatch('GET', '/test');
        $getOutput = ob_get_clean();

        ob_start();
        $router->dispatch('POST', '/test');
        $postOutput = ob_get_clean();

        ob_start();
        $router->dispatch('PUT', '/test');
        $putOutput = ob_get_clean();

        // Проверяем результаты
        $getResponse = json_decode($getOutput, true);
        $postResponse = json_decode($postOutput, true);
        $putResponse = json_decode($putOutput, true);

        $this->assertEquals('GET', $getResponse['method']);
        $this->assertEquals('POST', $postResponse['method']);
        $this->assertEquals('PUT', $putResponse['method']);

        $this->assertTrue($getResponse['valid']);
        $this->assertTrue($postResponse['valid']);
        $this->assertTrue($putResponse['valid']);
    }

    /**
     * Тест производительности интеграции Router + Validator
     */
    public function testPerformanceIntegration(): void
    {
        $router = new Router();

        $router->addRoute('POST', '/performance', function () {
            $startTime = microtime(true);

            // Тестируем 1000 валидаций
            for ($i = 0; $i < 1000; $i++) {
                $testString = str_repeat('(', $i % 10) . str_repeat(')', $i % 10);
                try {
                    Validator::validate($testString);
                } catch (\InvalidArgumentException $e) {
                    // Игнорируем исключения для пустых строк
                }
            }

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            echo json_encode([
                'operations' => 1000,
                'execution_time' => $executionTime,
                'ops_per_second' => 1000 / $executionTime
            ]);
        });

        ob_start();
        $router->dispatch('POST', '/performance');
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertEquals(1000, $response['operations']);
        $this->assertGreaterThan(0, $response['execution_time']);
        $this->assertGreaterThan(0, $response['ops_per_second']);

        // Проверяем, что производительность разумная (больше 1000 операций в секунду)
        $this->assertGreaterThan(1000, $response['ops_per_second']);
    }
}
