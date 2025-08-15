<?php

declare(strict_types=1);

namespace App\Controllers;

use JsonException;
use App\Http\JsonResponse;
use App\Http\ResponseSender;
use App\Redis\Health\RedisHealthChecker;

/**
 * Контроллер для получения статуса Redis Cluster
 */
class RedisHealthController
{
    private RedisHealthChecker $redisChecker;

    public function __construct(RedisHealthChecker $redisChecker)
    {
        $this->redisChecker = $redisChecker;
    }

    /**
     * Возвращает статус Redis Cluster
     * @return void
     * @throws JsonException
     */
    public function getStatus(): void
    {
        $redisStatus = $this->redisChecker->getClusterStatus();

        $response = JsonResponse::success(['redis_cluster' => $redisStatus]);

        ResponseSender::sendJson($response);

    }

    /**
     * Фабричный метод для создания контроллера с настройками по умолчанию
     */
    public static function createDefault(): RedisHealthController
    {
        return new self(new RedisHealthChecker());
    }
}