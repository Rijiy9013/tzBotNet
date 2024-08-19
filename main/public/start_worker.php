<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Services\QueueService;
use App\Services\RedisService;
use App\Workers\EventWorker;

$queueService = new QueueService();
$redisService = new RedisService();

$worker = new EventWorker($queueService, $redisService);
$worker->startMultipleWorkers(3);
