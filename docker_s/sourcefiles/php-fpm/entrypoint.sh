#!/bin/bash
set -e

cd /application

# Проверяем, существует ли vendor, если нет - устанавливаем зависимости
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-dev --no-scripts --no-autoloader --prefer-dist
    composer dump-autoload --optimize
    echo "Dependencies installed"
fi

# Выполняем оригинальную команду (php-fpm)
exec "$@"
