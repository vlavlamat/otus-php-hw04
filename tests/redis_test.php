<?php

// Простой скрипт для тестирования подключения к Redis Cluster

// Подключение автозагрузчика
require __DIR__ . '/../vendor/autoload.php';

// Тестирование прямого подключения к Redis Cluster
echo "Тестирование прямого подключения к Redis Cluster...\n";
try {
    $cluster = new \RedisCluster(null, [
        'redis-node1:6379',
        'redis-node2:6379'
    ]);

    // Попытка отправить ping-запрос к кластеру
    $pingResult = $cluster->ping('redis-node1:6379');
    echo "Результат ping: " . $pingResult . "\n";

    // Попытка установить и получить значение
    $cluster->set('test_key', 'test_value');
    $value = $cluster->get('test_key');
    echo "Результат получения: " . $value . "\n";

    echo "Подключение к Redis Cluster успешно!\n";
} catch (\Exception $e) {
    echo "Ошибка подключения к Redis Cluster: " . $e->getMessage() . "\n";
    echo "Код ошибки: " . $e->getCode() . "\n";
    echo "Трассировка ошибки: " . $e->getTraceAsString() . "\n";
}

// Тестирование подключения с использованием StatsCollector
echo "\nТестирование подключения с использованием StatsCollector...\n";
try {
    $statsCollector = new \App\StatsCollector();
    $isConnected = $statsCollector->isConnected();
    echo "Результат StatsCollector::isConnected(): " . ($isConnected ? 'true' : 'false') . "\n";
} catch (\Exception $e) {
    echo "Ошибка при использовании StatsCollector: " . $e->getMessage() . "\n";
    echo "Код ошибки: " . $e->getCode() . "\n";
    echo "Трассировка ошибки: " . $e->getTraceAsString() . "\n";
}
