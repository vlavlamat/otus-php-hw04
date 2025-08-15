<?php

declare(strict_types=1);

namespace App\Redis\Health;

use Exception;
use RedisCluster;
use RedisClusterException;

/**
 * Класс для проверки состояния Redis Cluster
 *
 * Предоставляет методы для мониторинга доступности узлов кластера
 * и определения общего состояния кластера на основе кворума.
 */
class RedisHealthChecker
{
    private RedisCluster $cluster;
    private array $config;

    /**
     * @param array|null $config Конфигурация Redis (по умолчанию загружается из файла)
     * @throws RedisClusterException При ошибке создания подключения к кластеру
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require __DIR__ . '/../../../config/redis.php';

        try {
            $this->cluster = new RedisCluster(
                null,
                $this->config['cluster']['nodes'],
                $this->config['cluster']['timeout'] ?? 5,
                $this->config['cluster']['read_timeout'] ?? 5
            );

        } catch (Exception $e) {
            // Если произошла любая ошибка при подключении или проверке соединения,
            // выбрасываем RedisClusterException
            throw new RedisClusterException(
                'Не удалось создать подключение к Redis Cluster: ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Возвращает общий статус кластера на основе кворума
     *
     * @return string 'connected' если доступно >= кворума узлов, иначе 'disconnected'
     */
    public function getClusterStatus(): string
    {
        try {
            $connectedCount = 0;
            $requiredQuorum = $this->config['cluster']['quorum'];

            foreach ($this->config['cluster']['nodes'] as $node) {
                try {
                    $pingResult = $this->cluster->ping($node);
                    if ($pingResult == 1 || $pingResult == '+PONG' || $pingResult === 'PONG') {
                        $connectedCount++;
                    }
                } catch (Exception) {
                    // Узел недоступен, продолжаем проверку
                    continue;
                }
            }
            return $connectedCount >= $requiredQuorum ? 'connected' : 'disconnected';
        } catch (Exception) {
            return 'disconnected';
        }
    }
}
