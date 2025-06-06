<?php

// Simple script to test Redis Cluster connection

// Include autoloader
require __DIR__ . '/../vendor/autoload.php';

// Test direct connection to Redis Cluster
echo "Testing direct connection to Redis Cluster...\n";
try {
    $cluster = new \RedisCluster(null, [
        'redis-node1:6379',
        'redis-node2:6379'
    ]);

    // Try to ping the cluster
    $pingResult = $cluster->ping('redis-node1:6379');
    echo "Ping result: " . $pingResult . "\n";

    // Try to set and get a value
    $cluster->set('test_key', 'test_value');
    $value = $cluster->get('test_key');
    echo "Get result: " . $value . "\n";

    echo "Redis Cluster connection successful!\n";
} catch (\Exception $e) {
    echo "Error connecting to Redis Cluster: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
}

// Test connection using StatsCollector
echo "\nTesting connection using StatsCollector...\n";
try {
    $statsCollector = new \App\StatsCollector();
    $isConnected = $statsCollector->isConnected();
    echo "StatsCollector::isConnected() result: " . ($isConnected ? 'true' : 'false') . "\n";
} catch (\Exception $e) {
    echo "Error using StatsCollector: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    echo "Error trace: " . $e->getTraceAsString() . "\n";
}
