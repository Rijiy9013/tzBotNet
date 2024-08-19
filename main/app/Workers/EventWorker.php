<?php

namespace App\Workers;

use App\Services\Interfaces\QueueServiceInterface;
use App\Services\Interfaces\RedisServiceInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class EventWorker
{
    private QueueServiceInterface $queueService;
    private RedisServiceInterface $redisService;
    private Logger $logger;

    public function __construct(QueueServiceInterface $queueService, RedisServiceInterface $redisService)
    {
        $this->queueService = $queueService;
        $this->redisService = $redisService;

        // Настраиваем логгер
        $logPath = __DIR__ . '/../../logs/events.log';
        $this->logger = new Logger('event_worker');
        $this->logger->pushHandler(new StreamHandler($logPath, Logger::INFO));
    }

    public function startMultipleWorkers(int $numberOfWorkers): void
    {
        $numberOfQueues = 50;
        if ($numberOfWorkers <= 0) {
            throw new InvalidArgumentException('Number of workers must be greater than zero.');
        }

        $queuesPerWorker = ceil($numberOfQueues / $numberOfWorkers);

        for ($workerId = 0; $workerId < $numberOfWorkers; $workerId++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('Could not fork');
            } else if ($pid) {
                continue;
            } else {
                echo "Worker {$workerId} started with PID " . getmypid() . "\n";
                $this->queueService->createConnection();

                for ($i = 0; $i < $queuesPerWorker; $i++) {
                    $queueIndex = ($workerId * $queuesPerWorker) + $i;
                    if ($queueIndex < $numberOfQueues) {
                        $queueName = "account_queue_" . $queueIndex;
                        echo "Worker {$workerId} registering on queue {$queueName}\n";
                        $this->queueService->consume($queueName, function (AMQPMessage $msg) use ($queueName) {
                            $eventData = json_decode($msg->body, true);
                            $accountId = $eventData['account_id'];
                            echo "Worker PID " . getmypid() . " processing message for account {$accountId} from queue {$queueName}\n";

                            $lockKey = "account_lock_{$accountId}";
                            $maxAttempts = 3;
                            $attempt = 0;
                            $lockAcquired = false;
                            $backoffTime = 500000; // Начальное время ожидания между попытками (0.1 сек)

                            while ($attempt < $maxAttempts) {
                                $attempt++;
                                try {
                                    if ($this->redisService->setLock($lockKey, 30)) {
                                        $lockAcquired = true;
                                        echo "Lock acquired for account {$accountId} on attempt {$attempt}\n";
                                        break;
                                    } else {
                                        echo "Could not acquire lock for account {$accountId}, attempt {$attempt}\n";
                                        usleep($backoffTime);
                                        $backoffTime *= 2; // Увеличиваем время ожидания с каждым разом
                                    }
                                } catch (RedisException $e) {
                                    echo "Redis error while trying to acquire lock for account {$accountId}: " . $e->getMessage() . "\n";
                                    usleep($backoffTime);
                                    $backoffTime *= 2; // Увеличиваем время ожидания с каждым разом
                                }
                            }

                            if ($lockAcquired) {
                                try {
                                    sleep(1); // Эмуляция обработки события
                                    $msg->ack();
                                    echo "Message processed and acknowledged for account {$accountId}\n";
                                } catch (Exception $e) {
                                    echo "Error processing message for account {$accountId}: " . $e->getMessage() . "\n";
                                } finally {
                                    try {
                                        $this->redisService->releaseLock($lockKey);
                                        echo "Lock released for account {$accountId}\n";
                                    } catch (RedisException $e) {
                                        echo "Redis error while releasing lock for account {$accountId}: " . $e->getMessage() . "\n";
                                    }
                                }
                            } else {
                                echo "Failed to acquire lock for account {$accountId} after {$maxAttempts} attempts\n";
                                $msg->nack(true);  // Сообщение возвращается в очередь
                            }
                        });
                    }
                }
                $this->queueService->wait();
                exit(0);
            }
        }

        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
            echo "Child exited with status {$status}\n";
        }
    }
}
