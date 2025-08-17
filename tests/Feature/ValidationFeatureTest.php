<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\App;
use PHPUnit\Framework\TestCase;
use App\Bootstrap\EnvironmentLoader;
use Tests\Support\MockPhpInputStream;

class ValidationFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        EnvironmentLoader::load();
        $this->app = new App();
        $this->app->run();
    }

    /**
     * @test
     * POST /validate с валидными скобками "(())" -> 200 + {"status": "valid"}
     */
    public function test_post_validate_with_valid_brackets_returns_200_with_valid_status(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/validate';
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        ob_start();

    }

    protected function tearDown(): void
    {
        // Сброс состояния после каждого теста
        http_response_code(200);
        if (ob_get_length()) {
            ob_end_clean();
        }
    }

    /**
     * Мокаем входные данные JSON для тестирования
     */
    private function mockJsonInput(string $json): void
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        // Мокаем php://input
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', MockPhpInputStream::class);
        MockPhpInputStream::$data = $json;
    }
}