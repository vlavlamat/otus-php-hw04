<?php

namespace Tests\Unit;

use App\Router;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    /**
     * Тест успешного добавления маршрута
     */
    public function testAddRouteSuccess(): void
    {
        $handler = function () {
            echo 'test';
        };

        $this->router->addRoute('GET', '/test', $handler);

        // Проверяем, что маршрут добавлен (косвенно через dispatch)
        $this->expectOutputString('test');
        $this->router->dispatch('GET', '/test');
    }

    /**
     * Тест добавления маршрута с различными HTTP методами
     */
    public function testAddRouteWithDifferentMethods(): void
    {
        $getHandler = function () {
            echo 'GET';
        };
        $postHandler = function () {
            echo 'POST';
        };

        $this->router->addRoute('GET', '/api/test', $getHandler);
        $this->router->addRoute('POST', '/api/test', $postHandler);

        // Проверяем GET
        $this->expectOutputString('GET');
        $this->router->dispatch('GET', '/api/test');
    }

    /**
     * Тест добавления маршрута с методом в нижнем регистре
     */
    public function testAddRouteWithLowercaseMethod(): void
    {
        $handler = function () {
            echo 'lowercase';
        };

        $this->router->addRoute('get', '/test', $handler);

        $this->expectOutputString('lowercase');
        $this->router->dispatch('GET', '/test');
    }

    /**
     * Тест исключения при дублировании маршрута
     */
    public function testAddRouteDuplicateThrowsException(): void
    {
        $handler1 = function () {
            echo 'first';
        };
        $handler2 = function () {
            echo 'second';
        };

        $this->router->addRoute('GET', '/test', $handler1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route GET /test already exists');

        $this->router->addRoute('GET', '/test', $handler2);
    }

    /**
     * Тест успешного dispatch с query string
     */
    public function testDispatchWithQueryString(): void
    {
        $handler = function () {
            echo 'query_test';
        };

        $this->router->addRoute('GET', '/api/test', $handler);

        $this->expectOutputString('query_test');
        $this->router->dispatch('GET', '/api/test?param=value&other=123');
    }

    /**
     * Тест dispatch с методом в нижнем регистре
     */
    public function testDispatchWithLowercaseMethod(): void
    {
        $handler = function () {
            echo 'lowercase_dispatch';
        };

        $this->router->addRoute('POST', '/api/validate', $handler);

        $this->expectOutputString('lowercase_dispatch');
        $this->router->dispatch('post', '/api/validate');
    }

    /**
     * Тест успешного добавления нескольких маршрутов
     */
    public function testMultipleRouteAddition(): void
    {
        $handler1 = function () { echo 'handler1'; };
        $handler2 = function () { echo 'handler2'; };
        $handler3 = function () { echo 'handler3'; };

        // Добавляем несколько маршрутов - если нет исключений, значит все работает
        $this->router->addRoute('GET', '/test1', $handler1);
        $this->router->addRoute('POST', '/test2', $handler2);
        $this->router->addRoute('PUT', '/test3', $handler3);

        // Если мы дошли до этой точки без исключений, тест прошел
        $this->assertTrue(true);
    }

    /**
     * Тест проверки дублирования маршрутов с разными методами
     */
    public function testSamePathDifferentMethods(): void
    {
        $getHandler = function () { echo 'GET'; };
        $postHandler = function () { echo 'POST'; };

        // Один путь, но разные методы - должно работать
        $this->router->addRoute('GET', '/api/test', $getHandler);
        $this->router->addRoute('POST', '/api/test', $postHandler);

        // Если нет исключений, тест прошел
        $this->assertTrue(true);
    }

    /**
     * Тест множественных маршрутов
     */
    public function testMultipleRoutes(): void
    {
        $handler1 = function () {
            echo 'route1';
        };
        $handler2 = function () {
            echo 'route2';
        };
        $handler3 = function () {
            echo 'route3';
        };

        $this->router->addRoute('GET', '/route1', $handler1);
        $this->router->addRoute('POST', '/route2', $handler2);
        $this->router->addRoute('PUT', '/route3', $handler3);

        // Тестируем каждый маршрут
        ob_start();
        $this->router->dispatch('GET', '/route1');
        $output1 = ob_get_clean();

        ob_start();
        $this->router->dispatch('POST', '/route2');
        $output2 = ob_get_clean();

        ob_start();
        $this->router->dispatch('PUT', '/route3');
        $output3 = ob_get_clean();

        $this->assertEquals('route1', $output1);
        $this->assertEquals('route2', $output2);
        $this->assertEquals('route3', $output3);
    }

    /**
     * Тест обработки сложных путей
     */
    public function testComplexPaths(): void
    {
        $handler = function () {
            echo 'complex';
        };

        $this->router->addRoute('GET', '/api/v1/users/123/profile', $handler);

        $this->expectOutputString('complex');
        $this->router->dispatch('GET', '/api/v1/users/123/profile');
    }

    /**
     * Тест обработки корневого пути
     */
    public function testRootPath(): void
    {
        $handler = function () {
            echo 'root';
        };

        $this->router->addRoute('GET', '/', $handler);

        $this->expectOutputString('root');
        $this->router->dispatch('GET', '/');
    }

    /**
     * Тест обработки пустого пути
     */
    public function testEmptyPath(): void
    {
        $handler = function () {
            echo 'empty';
        };

        $this->router->addRoute('GET', '', $handler);

        $this->expectOutputString('empty');
        $this->router->dispatch('GET', '');
    }
}
