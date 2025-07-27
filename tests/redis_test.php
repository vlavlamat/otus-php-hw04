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

// Тестирование подключения с использованием RedisHealthChecker
echo "\nТестирование подключения с использованием RedisHealthChecker...\n";
try {
    $healthChecker = new \App\Redis\Health\RedisHealthChecker();

    // Проверяем общее состояние кластера
    $isConnected = $healthChecker->isConnected();
    echo "Результат RedisHealthChecker::isConnected(): " . ($isConnected ? 'true' : 'false') . "\n";

    // Получаем детальный статус всех узлов
    echo "\nДетальный статус всех узлов:\n";
    $clusterStatus = $healthChecker->getClusterStatus();
    foreach ($clusterStatus as $node => $status) {
        echo "  $node: $status\n";
    }

    // Подсчитываем количество подключенных узлов
    $connectedCount = 0;
    foreach ($clusterStatus as $status) {
        if ($status === 'connected') {
            $connectedCount++;
        }
    }
    echo "\nПодключенных узлов: $connectedCount из " . count($clusterStatus) . "\n";
    // Проверяем достижение кворума (минимум 3 узла из 10)
    // Для нашей конфигурации мы установили кворум в 3 узла.
    // Это позволяет кластеру работать даже при отказе большинства узлов,
    // сохраняя при этом минимальную отказоустойчивость.
    echo "Кворум (минимум 3): " . ($connectedCount >= 3 ? 'ДОСТИГНУТ' : 'НЕ ДОСТИГНУТ') . "\n";

} catch (\Exception $e) {
    echo "Ошибка при использовании RedisHealthChecker: " . $e->getMessage() . "\n";
    echo "Код ошибки: " . $e->getCode() . "\n";
    echo "Трассировка ошибки: " . $e->getTraceAsString() . "\n";
}
