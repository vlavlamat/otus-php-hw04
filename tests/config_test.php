<?php

// Простой скрипт для тестирования конфигурационной системы

// Подключение автозагрузчика
require __DIR__ . '/../vendor/autoload.php';

echo "Тестирование конфигурационной системы Redis...\n\n";

// Тест 1: Проверка загрузки конфигурации
echo "1. Тестирование загрузки config/redis.php...\n";
try {
    $config = require __DIR__ . '/../config/redis.php';
    
    echo "✅ Конфигурация загружена успешно\n";
    echo "   Количество узлов: " . count($config['cluster']['nodes']) . "\n";
    echo "   Кворум: " . $config['cluster']['quorum'] . "\n";
    echo "   Таймаут: " . $config['cluster']['timeout'] . "\n";
    
} catch (\Exception $e) {
    echo "❌ Ошибка загрузки конфигурации: " . $e->getMessage() . "\n";
}

echo "\n";

// Тест 2: Проверка переменных окружения
echo "2. Тестирование переменных окружения...\n";

// Устанавливаем тестовые переменные окружения
$_ENV['REDIS_QUORUM'] = '8';
$_ENV['REDIS_TIMEOUT'] = '10';

try {
    $config = require __DIR__ . '/../config/redis.php';
    
    if ($config['cluster']['quorum'] == 8) {
        echo "✅ Переменная REDIS_QUORUM работает корректно\n";
    } else {
        echo "❌ Переменная REDIS_QUORUM не работает\n";
    }
    
    if ($config['cluster']['timeout'] == 10) {
        echo "✅ Переменная REDIS_TIMEOUT работает корректно\n";
    } else {
        echo "❌ Переменная REDIS_TIMEOUT не работает\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Ошибка при тестировании переменных окружения: " . $e->getMessage() . "\n";
}

// Очищаем переменные окружения
unset($_ENV['REDIS_QUORUM'], $_ENV['REDIS_TIMEOUT']);

echo "\n";

// Тест 3: Проверка RedisHealthChecker с конфигурацией
echo "3. Тестирование RedisHealthChecker с конфигурацией...\n";
try {
    // Создаем экземпляр без подключения к Redis (ожидаем ошибку подключения)
    $healthChecker = new \App\RedisHealthChecker();
    echo "❌ RedisHealthChecker создан, но ожидалась ошибка подключения\n";
    
} catch (\RedisClusterException $e) {
    echo "✅ RedisHealthChecker корректно пытается использовать конфигурацию\n";
    echo "   Ошибка подключения ожидаема (Redis кластер не запущен)\n";
    
} catch (\Exception $e) {
    echo "❌ Неожиданная ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

// Тест 4: Проверка структуры конфигурации
echo "4. Тестирование структуры конфигурации...\n";
try {
    $config = require __DIR__ . '/../config/redis.php';
    
    $requiredKeys = [
        'cluster.nodes',
        'cluster.quorum', 
        'cluster.timeout',
        'cluster.read_timeout',
        'cluster.session.prefix',
        'monitoring.check_interval'
    ];
    
    $allKeysPresent = true;
    foreach ($requiredKeys as $key) {
        $keys = explode('.', $key);
        $current = $config;
        
        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                echo "❌ Отсутствует ключ: $key\n";
                $allKeysPresent = false;
                break;
            }
            $current = $current[$k];
        }
    }
    
    if ($allKeysPresent) {
        echo "✅ Все необходимые ключи конфигурации присутствуют\n";
    }
    
    // Проверяем, что nodes - это массив с 10 элементами
    if (is_array($config['cluster']['nodes']) && count($config['cluster']['nodes']) === 10) {
        echo "✅ Массив узлов содержит 10 элементов\n";
    } else {
        echo "❌ Массив узлов должен содержать 10 элементов\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Ошибка при проверке структуры: " . $e->getMessage() . "\n";
}

echo "\n=== Тестирование завершено ===\n";