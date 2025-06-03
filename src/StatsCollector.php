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
}