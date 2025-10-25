FROM php:8.3-fpm

WORKDIR /application

# Устанавливаем пакеты
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev libpq-dev zip unzip

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo_pgsql pgsql mbstring gd zip

# Устанавливаем Redis
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Изменяем UID и GID пользователя www-data на 1000 (совместимость с Windows)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Создаем директорию для Unix socket и меняем владельца
RUN mkdir -p /var/run/php && chown www-data:www-data /var/run/php

CMD ["php-fpm", "-F", "-R"]
