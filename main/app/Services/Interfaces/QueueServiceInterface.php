<?php

namespace App\Services\Interfaces;

interface QueueServiceInterface
{
    public function publish(string $queueName, array $message): void;
    public function consume(string $queueName, callable $callback): void;
    public function wait(): void;
}
