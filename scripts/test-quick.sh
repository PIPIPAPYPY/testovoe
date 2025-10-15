#!/bin/bash

# Скрипт для быстрого тестирования на SQLite (для PR)

set -e

echo "⚡ Быстрое тестирование на SQLite..."

# Настраиваем переменные окружения для SQLite
export DB_CONNECTION=sqlite
export DB_DATABASE=database/testing.sqlite

# Создаем файл базы данных если не существует
touch database/testing.sqlite

echo "🧪 Запуск быстрых тестов..."

# Запускаем миграции
php artisan migrate --force

echo "📋 Unit тесты..."
php artisan test --testsuite=Unit

echo "🔧 Smoke Feature тесты..."
php artisan test --filter="BasicFeatureTest|SimpleTaskTest|WorkingTaskTest|SimpleApiTest|SimpleAuthTest|WorkingApiTest"

echo "✅ Быстрые тесты завершены!"



