<?php

return [
    'rabbitmq' => [
        'host' => 'rabbitmq',          // Хост RabbitMQ (имя сервиса в Docker)
        'port' => 5672,                // Порт RabbitMQ
        'user' => 'guest',             // Пользователь RabbitMQ
        'password' => 'guest',         // Пароль RabbitMQ
    ],
    'redis' => [
        'host' => 'redis',             // Хост Redis (имя сервиса в Docker)
        'port' => 6379,                // Порт Redis
    ],
];
