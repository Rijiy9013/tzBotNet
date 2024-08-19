<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Services\Interfaces\QueueServiceInterface;

class QueueService implements QueueServiceInterface
{
    private $channel;
    private $connection;

    public function __construct()
    {
        $this->createConnection();
    }

    public function createConnection(): void
    {
        $config = require __DIR__ . '/../Config/config.php';
        $this->connection = new AMQPStreamConnection(
            $config['rabbitmq']['host'],
            $config['rabbitmq']['port'],
            $config['rabbitmq']['user'],
            $config['rabbitmq']['password'],
            '/',            // виртуальный хост
            false,          // insistent
            'AMQPLAIN',     // механизм аутентификации
            null,           // не используется
            'en_US',        // локаль
            3.0,            // время ожидания соединения
            3.0,            // время ожидания чтения
            null,           // контекст
            false,          // keepalive
            60              // heartbeat интервал в секундах
        );
        $this->channel = $this->connection->channel();
        $this->channel->basic_qos(
            (int)null,  // размер окна, оставьте null, если не используется
            10,    // количество сообщений, которые может обработать канал одновременно
            null   // глобальный или нет (null означает не глобальный)
        );
    }

    public function publish(string $queueName, array $message): void
    {
        $this->channel->queue_declare($queueName, false, true, false, false);
        $msg = new AMQPMessage(json_encode($message), ['delivery_mode' => 2]);
        $this->channel->basic_publish($msg, '', $queueName);
    }

    public function consume(string $queueName, callable $callback): void
    {
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->basic_consume($queueName, '', false, false, false, false, $callback);
    }

    public function wait(): void
    {
        while ($this->channel->is_consuming()) {
            $this->channel->wait(null, true, 10);
        }
    }

    public function closeConnection(): void
    {
        $this->channel->close();
        $this->connection->close();
    }
}
