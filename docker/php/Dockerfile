# Используем официальный PHP образ
FROM php:8.2-fpm

# Установка необходимых расширений и инструментов
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql zip sockets \
    && docker-php-ext-install pcntl

# Установка расширения Redis
RUN pecl install redis && docker-php-ext-enable redis

# Установка Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Копируем код приложения
COPY main /var/www/html

# Установка зависимостей
WORKDIR /var/www/html

# Устанавливаем переменную окружения для Composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Установка зависимостей через Composer
RUN composer install

# Настройка прав доступа
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Запуск PHP-FPM
CMD ["php-fpm"]
