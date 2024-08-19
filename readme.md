# Сервис обработки событий

Этот проект представляет собой PHP-сервис, который обрабатывает события для множества аккаунтов, используя RabbitMQ для очередей сообщений и Redis для распределенной блокировки.

## Настройка

Для настройки проекта выполните следующие шаги:

1. **Соберите и запустите контейнеры**:
   ```bash
   docker-compose up -d --build

2. **Установите зависимости PHP**:
    ```bash
    docker exec -it php_app composer install

3. **Запуск**:
    ```bash
    docker exec -it php_app php /var/www/html/app/tests/EventProcessingTest.php
