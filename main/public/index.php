<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\EventController;
use App\Services\QueueService;
use App\Services\RedisService;
use Illuminate\Http\Request;

if ($_SERVER['REQUEST_URI'] === '/api/events' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $queueService = new QueueService();
    $redisService = new RedisService();

    $controller = new EventController($queueService, $redisService);
    $request = json_decode(file_get_contents('php://input'), true);
    $controller->receiveEvent(new Request($request));
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}
