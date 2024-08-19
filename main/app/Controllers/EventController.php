<?php

namespace App\Controllers;

use App\Services\Interfaces\QueueServiceInterface;
use App\Services\Interfaces\RedisServiceInterface;
use Illuminate\Http\Request;

class EventController
{
    private QueueServiceInterface $queueService;
    private RedisServiceInterface $redisService;

    public function __construct(QueueServiceInterface $queueService, RedisServiceInterface $redisService)
    {
        $this->queueService = $queueService;
        $this->redisService = $redisService;
    }

    public function receiveEvent(Request $request): void
    {
        $accountId = $request->input('account_id');
        $eventData = $request->input('event_data');

        // Убедимся, что $eventData является массивом
        if (!is_array($eventData)) {
            $eventData = ['data' => $eventData]; // Преобразуем строку в массив
        }

        $this->redisService->addAccountQueue($accountId);
        $this->queueService->publish($accountId, $eventData);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'Event received']);
    }
}
