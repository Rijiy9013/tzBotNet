<?php

namespace App\Services;

use App\Services\Interfaces\RedisServiceInterface;
use Redis;

class RedisService implements RedisServiceInterface
{
    private Redis $redis;

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $this->redis = new Redis();
        try {
            $config = require __DIR__ . '/../Config/config.php';
            $this->redis->connect($config['redis']['host'], $config['redis']['port']);
        } catch (\RedisException $e) {
            echo "Failed to connect to Redis: " . $e->getMessage() . "\n";
            // Повторное подключение через некоторое время
            sleep(1);
            $this->connect();
        }
    }

    public function setLock(string $key, int $ttl): bool
    {
        try {
            return $this->redis->set($key, 'locked', ['nx', 'ex' => $ttl]);
        } catch (\RedisException $e) {
            echo "Redis error while setting lock: " . $e->getMessage() . "\n";
            $this->connect();
            return false;
        }
    }

    public function releaseLock(string $key): void
    {
        try {
            $this->redis->del($key) > 0;
        } catch (\RedisException $e) {
            echo "Redis error while releasing lock: " . $e->getMessage() . "\n";
            $this->connect();;
        }
    }

    public function addAccountQueue(string $accountId): void
    {
        $this->redis->sAdd('account_queues', $accountId);
    }

    public function getAccountQueues(): array
    {
        return $this->redis->sMembers('account_queues');
    }
}
