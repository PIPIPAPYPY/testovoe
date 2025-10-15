#!/bin/bash

# Скрипт для тестирования всех версий PostgreSQL

set -e

echo "🐘 Тестирование всех версий PostgreSQL..."

# Запускаем Docker Compose
echo "🚀 Запуск PostgreSQL контейнеров..."
docker-compose -f docker-compose.test.yml up -d

# Ждем, пока все контейнеры будут готовы
echo "⏳ Ожидание готовности PostgreSQL контейнеров..."
sleep 30

# Функция для тестирования конкретной версии PostgreSQL
test_postgresql_version() {
    local version=$1
    local port=$2
    local db_name=$3
    
    echo "🧪 Тестирование PostgreSQL $version на порту $port..."
    
    # Настраиваем переменные окружения
    export DB_CONNECTION=pgsql
    export DB_HOST=localhost
    export DB_PORT=$port
    export DB_DATABASE=$db_name
    export DB_USERNAME=postgres
    export DB_PASSWORD=postgres
    
    # Запускаем миграции
    php artisan migrate --force
    
    # Запускаем тесты
    echo "📋 Unit тесты для PostgreSQL $version..."
    php artisan test --testsuite=Unit
    
    echo "🔧 Feature тесты для PostgreSQL $version..."
    php artisan test --testsuite=Feature
    
    echo "🐘 PostgreSQL совместимость для версии $version..."
    php artisan test --testsuite=PostgreSQL
    
    echo "⚡ Тесты производительности для PostgreSQL $version..."
    php artisan test --testsuite=Performance
    
    echo "✅ Тесты для PostgreSQL $version завершены!"
}

# Тестируем все версии
test_postgresql_version "13" "5434" "testing_13"
test_postgresql_version "14" "5435" "testing_14"
test_postgresql_version "15" "5433" "testing"
test_postgresql_version "16" "5436" "testing_16"

# Останавливаем контейнеры
echo "🛑 Остановка PostgreSQL контейнеров..."
docker-compose -f docker-compose.test.yml down

echo "✅ Все тесты PostgreSQL завершены успешно!"



