#!/bin/bash

# Скрипт для тестирования против PostgreSQL локально

set -e

echo "🐘 Настройка PostgreSQL для тестирования..."

# Проверяем, что PostgreSQL запущен
if ! pg_isready -q; then
    echo "❌ PostgreSQL не запущен. Запустите PostgreSQL и попробуйте снова."
    exit 1
fi

# Создаем тестовую базу данных
DB_NAME="testing_$(date +%s)"
echo "📦 Создание тестовой базы данных: $DB_NAME"

createdb $DB_NAME 2>/dev/null || {
    echo "❌ Не удалось создать базу данных. Проверьте права доступа к PostgreSQL."
    exit 1
}

# Настраиваем переменные окружения
export DB_CONNECTION=pgsql
export DB_HOST=localhost
export DB_PORT=5432
export DB_DATABASE=$DB_NAME
export DB_USERNAME=postgres
export DB_PASSWORD=postgres

echo "🧪 Запуск тестов против PostgreSQL..."

# Запускаем миграции
php artisan migrate --force

# Запускаем тесты
echo "📋 Unit тесты..."
php artisan test --testsuite=Unit

echo "🔧 Feature тесты..."
php artisan test --testsuite=Feature

echo "🐘 PostgreSQL совместимость..."
php artisan test --testsuite=PostgreSQL

echo "⚡ Тесты производительности..."
php artisan test --testsuite=Performance

# Очистка
echo "🧹 Очистка тестовой базы данных..."
dropdb $DB_NAME

echo "✅ Все тесты прошли успешно!"



