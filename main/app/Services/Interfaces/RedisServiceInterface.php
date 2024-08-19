<?php

namespace App\Services\Interfaces;

interface RedisServiceInterface
{
    public function addAccountQueue(string $accountId): void;
    public function getAccountQueues(): array;
    public function setLock(string $key, int $ttl): bool;
    public function releaseLock(string $key): void;
}
