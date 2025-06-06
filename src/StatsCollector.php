<?php
// Новый файл: src/StatsCollector.php
namespace App;

class StatsCollector
{
    private $cluster;

    public function __construct()
    {
        $this->cluster = new \RedisCluster(null, [
            'redis-node1:6379',
            'redis-node2:6379'
        ]);
    }

    public function incrementValidationCounter($string, $isValid): void
    {
        $key = $isValid ? 'stats:valid' : 'stats:invalid';
        $this->cluster->incr($key);

        // Сохраняем последние строки
        $historyKey = 'stats:history';
        $entry = json_encode([
            'string' => $string,
            'valid' => $isValid,
            'timestamp' => time()
        ]);

        $this->cluster->lpush($historyKey, $entry);
        $this->cluster->ltrim($historyKey, 0, 99); // Храним 100 записей
    }

    public function getStats(): array
    {
        return [
            'valid_count' => (int)$this->cluster->get('stats:valid'),
            'invalid_count' => (int)$this->cluster->get('stats:invalid'),
            'recent_history' => array_map(
                'json_decode',
                $this->cluster->lrange('stats:history', 0, 9)
            )
        ];
    }

    /**
     * Check if Redis Cluster is connected
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected(): bool
    {
        try {
            // Attempt to ping the Redis Cluster
            // The ping method requires a node name as an argument
            // The ping method returns 1 (as integer or string) if successful
            $pingResult = $this->cluster->ping('redis-node1:6379');
            return $pingResult == 1 || $pingResult === '+PONG' || $pingResult === true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
