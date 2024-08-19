<?php

require __DIR__ . '/../../vendor/autoload.php';

use App\Services\QueueService;
use App\Services\RedisService;
use App\Workers\EventWorker;

class EventProcessingTest
{
    private QueueService $queueService;
    private RedisService $redisService;
    private EventWorker $eventWorker;
    private array $accounts;
    private int $numberOfEvents;

    public function __construct(int $numberOfEvents = 1000)
    {
        $this->accounts = range(1, 1000); // 1000 аккаунтов
        $this->numberOfEvents = $numberOfEvents; // Количество событий, которые нужно отправить
    }

    public function initializeServices()
    {
        $this->queueService = new QueueService();
        $this->redisService = new RedisService();
        $this->eventWorker = new EventWorker($this->queueService, $this->redisService);
    }

    public function sendEvents()
    {
        for ($i = 1; $i <= $this->numberOfEvents; $i++) {
            $accountId = $this->accounts[array_rand($this->accounts)];
            $queueName = $this->getQueueNameForAccount($accountId);
            $message = [
                'account_id' => $accountId,
                'event_data' => "Event {$i} for account {$accountId}"
            ];
            $this->queueService->publish($queueName, $message);
            echo "Event {$i} published to {$queueName} for account {$accountId}\n";
        }
    }

    private function getQueueNameForAccount($accountId)
    {
        $numberOfQueues = 50;
        return 'account_queue_' . ($accountId % $numberOfQueues);
    }

    public function run()
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die('Could not fork process');
        } else if ($pid > 0) {
            // Родительский процесс: отправляем события
            echo "In parent process, PID: {$pid}\n";

            // Инициализация сервисов после форка
            $this->initializeServices();

            // Даем воркеру время запуститься
            sleep(2);

            // Вызов метода отправки сообщений
            echo "Sending events...\n";
            $this->sendEvents();

            // Ждем завершения дочернего процесса
            pcntl_wait($status);
            echo "Parent process complete.\n";
        } else {
            // Дочерний процесс: запускаем воркеров
            echo "In child process, starting multiple workers...\n";

            // Инициализация сервисов после форка
            $this->initializeServices();

            // Запуск нескольких воркеров
            $this->eventWorker->startMultipleWorkers(5); // Запуск 5 воркеров для обработки

            exit(0);
        }
    }
}

// Запуск теста
$test = new EventProcessingTest(5000); // Количество событий можно изменить по необходимости
$test->run();
