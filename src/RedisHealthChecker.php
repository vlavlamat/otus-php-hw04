<?php
declare(strict_types=1);

namespace App;

use RedisCluster;
use RedisClusterException;
use Exception;

/**
 * Класс RedisHealthChecker
 *
 * Отвечает за проверку состояния Redis Cluster и предоставление
 * информации о доступности кластера для мониторинга и отображения
 * статуса соединения на фронтенде.
 */
class RedisHealthChecker
{
    /**
     * Экземпляр подключения к Redis Cluster
     *
     * @var RedisCluster
     */
    private $cluster;

    /**
     * Конструктор класса
     *
     * Инициализирует подключение к Redis Cluster, используя пять узлов.
     * При создании экземпляра класса автоматически устанавливается соединение
     * с кластером Redis.
     *
     * @throws RedisClusterException если невозможно подключиться к Redis Cluster
     */
    public function __construct()
    {
        // Инициализируем массив серверов Redis Cluster
        $this->cluster = new RedisCluster(null, [
            'redis-node1:6379', // Первый узел кластера Redis
            'redis-node2:6379', // Второй узел кластера Redis
            'redis-node3:6379', // Третий узел кластера Redis
            'redis-node4:6379', // Четвертый узел кластера Redis
            'redis-node5:6379'  // Пятый узел кластера Redis
        ]);
    }

    /**
     * Проверяет состояние всех узлов Redis кластера
     *
     * Метод отправляет ping-запрос к каждому узлу Redis Cluster
     * и возвращает массив со статусом каждого узла.
     *
     * @return array Ассоциативный массив, где ключи - имена узлов, значения - их статус
     */
    public function getClusterStatus(): array
    {
        // Список всех узлов Redis Cluster для проверки
        $nodes = [
            'redis-node1:6379',
            'redis-node2:6379',
            'redis-node3:6379',
            'redis-node4:6379',
            'redis-node5:6379'
        ];

        $status = []; // Инициализируем массив для хранения статусов

        // Проверяем каждый узел отдельно
        foreach ($nodes as $node) {
            try {
                // Отправляем ping конкретному узлу
                $pingResult = $this->cluster->ping($node);

                // Проверяем ответ: '+PONG' или 1 означает успешное соединение
                $status[$node] = ($pingResult == 1 || $pingResult === '+PONG' || $pingResult === 'PONG')
                    ? 'connected'    // Узел доступен
                    : 'disconnected'; // Узел недоступен или вернул неожиданный ответ
            } catch (Exception $e) {
                // Если произошла ошибка, сохраняем информацию о ней
                $status[$node] = 'error: ' . $e->getMessage();
            }
        }

        return $status; // Возвращаем статусы всех узлов
    }

    /**
     * Проверяет общее состояние Redis Cluster
     *
     * Метод определяет, считается ли кластер работоспособным.
     * Кластер считается доступным, если не менее 3 узлов из 5 работают корректно.
     * Это обеспечивает отказоустойчивость кластера при выходе из строя
     * меньшинства узлов.
     *
     * @return bool true, если кластер работоспособен, false в противном случае
     */
    public function isConnected(): bool
    {
        // Получаем статус всех узлов
        $status = $this->getClusterStatus();
        $connectedCount = 0;

        // Подсчитываем количество доступных узлов
        foreach ($status as $nodeStatus) {
            if ($nodeStatus === 'connected') {
                $connectedCount++;
            }
        }

        // Кластер доступен, если минимум 3 узла работают
        // (обеспечивает кворум для Redis Cluster)
        return $connectedCount >= 3;
    }
}
