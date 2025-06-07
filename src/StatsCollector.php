<?php
// Новый файл: src/StatsCollector.php
namespace App;

/**
 * Класс StatsCollector
 * 
 * Отвечает за сбор и хранение статистики валидации скобочных последовательностей.
 * Использует Redis Cluster для хранения данных о количестве валидных и невалидных
 * последовательностей, а также истории последних проверок.
 */
class StatsCollector
{
    /**
     * Экземпляр подключения к Redis Cluster
     * 
     * @var \RedisCluster
     */
    private $cluster;

    /**
     * Конструктор класса
     * 
     * Инициализирует подключение к Redis Cluster, используя три узла:
     * redis-node1:6379, redis-node2:6379 и redis-node3:6379
     */
    public function __construct()
    {
        $this->cluster = new \RedisCluster(null, [
            'redis-node1:6379', // Первый узел кластера Redis
            'redis-node2:6379', // Второй узел кластера Redis
            'redis-node3:6379'  // Третий узел кластера Redis
        ]);
    }

    /**
     * Увеличивает счетчик валидаций и сохраняет информацию о проверенной строке
     * 
     * @param string $string - Проверяемая строка со скобками
     * @param bool $isValid - Результат валидации (true - валидна, false - невалидна)
     * @return void
     */
    public function incrementValidationCounter($string, $isValid): void
    {
        try {
            // Выбираем ключ в зависимости от результата валидации
            $key = $isValid ? 'stats:valid' : 'stats:invalid';
            // Увеличиваем соответствующий счетчик на 1
            $this->cluster->incr($key);

            // Сохраняем последние строки в историю
            $historyKey = 'stats:history';
            // Формируем запись для истории в формате JSON
            $entry = json_encode([
                'string' => $string,      // Проверенная строка
                'valid' => $isValid,      // Результат валидации
                'timestamp' => time()     // Временная метка
            ]);

            // Добавляем запись в начало списка истории
            $this->cluster->lpush($historyKey, $entry);
            // Ограничиваем список истории 100 последними записями
            $this->cluster->ltrim($historyKey, 0, 99); // Храним 100 записей
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем выполнение приложения
            // В реальном приложении здесь должно быть логирование
            // error_log('Ошибка при сохранении статистики: ' . $e->getMessage());
        }
    }

    /**
     * Получает статистику валидаций
     * 
     * @return array Массив со статистикой, содержащий:
     *   - valid_count: количество валидных последовательностей
     *   - invalid_count: количество невалидных последовательностей
     *   - recent_history: последние 10 записей из истории валидаций
     */
    public function getStats(): array
    {
        try {
            return [
                // Получаем количество валидных последовательностей
                'valid_count' => (int)$this->cluster->get('stats:valid'),
                // Получаем количество невалидных последовательностей
                'invalid_count' => (int)$this->cluster->get('stats:invalid'),
                // Получаем последние 10 записей из истории и преобразуем их из JSON
                'recent_history' => array_map(
                    'json_decode',
                    $this->cluster->lrange('stats:history', 0, 9)
                )
            ];
        } catch (\Exception $e) {
            // В случае ошибки возвращаем пустую статистику
            return [
                'valid_count' => 0,
                'invalid_count' => 0,
                'recent_history' => [],
                'error' => 'Ошибка при получении статистики: Redis недоступен'
            ];
        }
    }

    /**
     * Проверяет подключение к Redis Cluster
     * 
     * Метод отправляет ping-запрос к первому узлу кластера и анализирует ответ.
     * 
     * @return bool Возвращает true, если подключение активно, false в противном случае
     */
    public function isConnected(): bool
    {
        try {
            // Пытаемся отправить ping-запрос к Redis Cluster
            // Метод ping требует имя узла в качестве аргумента
            // Метод ping возвращает 1 (как целое число или строку) или '+PONG' при успешном подключении
            $pingResult = $this->cluster->ping('redis-node1:6379');
            // Проверяем различные варианты успешного ответа
            return $pingResult == 1 || $pingResult === '+PONG' || $pingResult === true;
        } catch (\Exception $e) {
            // В случае исключения (ошибки подключения) возвращаем false
            return false;
        }
    }
}
