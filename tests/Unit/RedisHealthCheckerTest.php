<?php

namespace Tests\Unit;

use App\RedisHealthChecker;
use PHPUnit\Framework\TestCase;
use RedisClusterException;

class RedisHealthCheckerTest extends TestCase
{
    /**
     * Тест успешного создания экземпляра RedisHealthChecker
     * Поскольку конструктор пытается подключиться к Redis, ожидаем исключение
     */
    public function testConstructorThrowsExceptionWhenRedisUnavailable(): void
    {
        // Создаем конфигурацию с недоступными узлами Redis
        $invalidConfig = [
            'cluster' => [
                'nodes' => [
                    'invalid-redis-host:6379',
                    'another-invalid-host:6379'
                ],
                'timeout' => 1,
                'read_timeout' => 1,
                'quorum' => 1
            ]
        ];

        $this->expectException(RedisClusterException::class);
        new RedisHealthChecker($invalidConfig);
    }

    /**
     * Тест конфигурации по умолчанию
     */
    public function testDefaultConfiguration(): void
    {
        // Проверяем, что конфигурация загружается корректно
        $configPath = __DIR__ . '/../../config/redis.php';
        $this->assertFileExists($configPath);

        $config = require $configPath;

        $this->assertIsArray($config);
        $this->assertArrayHasKey('cluster', $config);
        $this->assertArrayHasKey('nodes', $config['cluster']);
        $this->assertArrayHasKey('quorum', $config['cluster']);

        // Проверяем, что есть 10 узлов
        $this->assertCount(10, $config['cluster']['nodes']);

        // Проверяем, что кворум имеет ожидаемое значение
        $this->assertEquals(3, $config['cluster']['quorum']);
        $this->assertLessThanOrEqual(10, $config['cluster']['quorum']);
    }

    /**
     * Тест структуры конфигурации
     */
    public function testConfigurationStructure(): void
    {
        $config = require __DIR__ . '/../../config/redis.php';

        // Проверяем основную структуру
        $this->assertArrayHasKey('cluster', $config);
        $this->assertArrayHasKey('monitoring', $config);

        // Проверяем структуру cluster
        $cluster = $config['cluster'];
        $this->assertArrayHasKey('nodes', $cluster);
        $this->assertArrayHasKey('quorum', $cluster);
        $this->assertArrayHasKey('timeout', $cluster);
        $this->assertArrayHasKey('read_timeout', $cluster);
        $this->assertArrayHasKey('session', $cluster);

        // Проверяем типы данных
        $this->assertIsArray($cluster['nodes']);
        $this->assertIsInt($cluster['quorum']);
        $this->assertIsInt($cluster['timeout']);
        $this->assertIsInt($cluster['read_timeout']);
        $this->assertIsArray($cluster['session']);

        // Проверяем узлы
        foreach ($cluster['nodes'] as $node) {
            $this->assertIsString($node);
            $this->assertStringContainsString(':', $node);
        }
    }

    /**
     * Тест переменных окружения в конфигурации
     */
    public function testEnvironmentVariables(): void
    {
        // Сохраняем текущие переменные окружения
        $originalQuorum = $_ENV['REDIS_QUORUM'] ?? null;
        $originalTimeout = $_ENV['REDIS_TIMEOUT'] ?? null;

        // Устанавливаем тестовые переменные
        $_ENV['REDIS_QUORUM'] = '8';
        $_ENV['REDIS_TIMEOUT'] = '10';

        // Перезагружаем конфигурацию
        $config = require __DIR__ . '/../../config/redis.php';

        $this->assertEquals(8, $config['cluster']['quorum']);
        $this->assertEquals(10, $config['cluster']['timeout']);

        // Восстанавливаем переменные окружения
        if ($originalQuorum !== null) {
            $_ENV['REDIS_QUORUM'] = $originalQuorum;
        } else {
            unset($_ENV['REDIS_QUORUM']);
        }

        if ($originalTimeout !== null) {
            $_ENV['REDIS_TIMEOUT'] = $originalTimeout;
        } else {
            unset($_ENV['REDIS_TIMEOUT']);
        }
    }

    /**
     * Тест значений по умолчанию в конфигурации
     */
    public function testDefaultConfigurationValues(): void
    {
        // Убеждаемся, что переменные окружения не установлены
        unset($_ENV['REDIS_QUORUM']);
        unset($_ENV['REDIS_TIMEOUT']);
        unset($_ENV['REDIS_READ_TIMEOUT']);

        $config = require __DIR__ . '/../../config/redis.php';

        // Проверяем значения по умолчанию
        $this->assertEquals(3, $config['cluster']['quorum']);
        $this->assertEquals(5, $config['cluster']['timeout']);
        $this->assertEquals(5, $config['cluster']['read_timeout']);
    }

    /**
     * Тест валидности узлов в конфигурации
     */
    public function testNodeValidation(): void
    {
        $config = require __DIR__ . '/../../config/redis.php';
        $nodes = $config['cluster']['nodes'];

        $this->assertCount(10, $nodes);

        $expectedNodes = [
            'redis-node1:6379',
            'redis-node2:6379',
            'redis-node3:6379',
            'redis-node4:6379',
            'redis-node5:6379',
            'redis-node6:6379',
            'redis-node7:6379',
            'redis-node8:6379',
            'redis-node9:6379',
            'redis-node10:6379'
        ];

        $this->assertEquals($expectedNodes, $nodes);
    }

    /**
     * Тест конфигурации сессий
     */
    public function testSessionConfiguration(): void
    {
        $config = require __DIR__ . '/../../config/redis.php';
        $session = $config['cluster']['session'];

        $this->assertArrayHasKey('prefix', $session);
        $this->assertArrayHasKey('gc_maxlifetime', $session);
        $this->assertArrayHasKey('gc_probability', $session);
        $this->assertArrayHasKey('gc_divisor', $session);

        $this->assertIsString($session['prefix']);
        $this->assertIsInt($session['gc_maxlifetime']);
        $this->assertIsInt($session['gc_probability']);
        $this->assertIsInt($session['gc_divisor']);

        // Проверяем разумные значения
        $this->assertGreaterThan(0, $session['gc_maxlifetime']);
        $this->assertGreaterThanOrEqual(0, $session['gc_probability']);
        $this->assertGreaterThan(0, $session['gc_divisor']);
    }

    /**
     * Тест конфигурации мониторинга
     */
    public function testMonitoringConfiguration(): void
    {
        $config = require __DIR__ . '/../../config/redis.php';
        $monitoring = $config['monitoring'];

        $this->assertArrayHasKey('check_interval', $monitoring);
        $this->assertArrayHasKey('ping_timeout', $monitoring);

        $this->assertIsInt($monitoring['check_interval']);
        $this->assertIsInt($monitoring['ping_timeout']);

        // Проверяем разумные значения
        $this->assertGreaterThan(0, $monitoring['check_interval']);
        $this->assertGreaterThan(0, $monitoring['ping_timeout']);
    }
}
