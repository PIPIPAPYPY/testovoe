# Makefile для управления тестами

.PHONY: help test test-quick test-postgresql test-all-postgresql test-performance test-coverage clean

# Показать справку
help:
	@echo "Доступные команды:"
	@echo "  test              - Запустить все тесты на SQLite"
	@echo "  test-quick        - Быстрые тесты для PR (Unit + Smoke Feature)"
	@echo "  test-postgresql   - Тесты на PostgreSQL (требует запущенный PostgreSQL)"
	@echo "  test-all-postgresql - Тесты на всех версиях PostgreSQL через Docker"
	@echo "  test-performance  - Тесты производительности"
	@echo "  test-coverage     - Тесты с покрытием кода"
	@echo "  clean             - Очистка кэша и временных файлов"

# Стандартные тесты на SQLite
test:
	@echo "🧪 Запуск всех тестов на SQLite..."
	php artisan test

# Быстрые тесты для PR
test-quick:
	@echo "⚡ Запуск быстрых тестов для PR..."
	@chmod +x scripts/test-quick.sh
	./scripts/test-quick.sh

# Тесты на PostgreSQL (требует запущенный PostgreSQL)
test-postgresql:
	@echo "🐘 Запуск тестов на PostgreSQL..."
	@chmod +x scripts/test-postgresql.sh
	./scripts/test-postgresql.sh

# Тесты на всех версиях PostgreSQL через Docker
test-all-postgresql:
	@echo "🐘 Запуск тестов на всех версиях PostgreSQL..."
	@chmod +x scripts/test-all-postgresql.sh
	./scripts/test-all-postgresql.sh

# Тесты производительности
test-performance:
	@echo "⚡ Запуск тестов производительности..."
	php artisan test --testsuite=Performance

# Тесты безопасности
test-security:
	@echo "🔒 Запуск тестов безопасности..."
	php artisan test --testsuite=Security

# Тесты RBAC/авторизации
test-rbac:
	@echo "🛡️ Запуск тестов RBAC/авторизации..."
	php artisan test --filter="RbacTest|DataAccessSecurityTest|ApiAuthorizationTest|TokenSecurityTest"

# Тесты внешних интеграций
test-external:
	@echo "🌐 Запуск тестов внешних интеграций..."
	php artisan test --testsuite=External

# Тесты с HTTP мокированием
test-mocking:
	@echo "🎭 Запуск тестов с HTTP мокированием..."
	php artisan test --filter="NotificationServiceTest|AnalyticsProviderServiceTest|ErrorScenarioTest|VcrFixtureTest"

# Тесты с покрытием кода
test-coverage:
	@echo "📊 Запуск тестов с покрытием кода..."
	php artisan test --coverage --coverage-html=coverage

# Очистка
clean:
	@echo "🧹 Очистка кэша и временных файлов..."
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	rm -rf storage/framework/cache/*
	rm -rf storage/framework/sessions/*
	rm -rf storage/framework/views/*
	rm -rf .phpunit.result.cache
	rm -rf coverage

# Установка зависимостей
install:
	@echo "📦 Установка зависимостей..."
	composer install
	npm install

# Настройка проекта
setup: install
	@echo "⚙️ Настройка проекта..."
	php -r "file_exists('.env') || copy('.env.example', '.env');"
	php artisan key:generate
	php artisan migrate
	@chmod +x scripts/*.sh

# Запуск сервера разработки
dev:
	@echo "🚀 Запуск сервера разработки..."
	php artisan serve

# Запуск Docker окружения для тестирования
docker-test-up:
	@echo "🐳 Запуск Docker окружения для тестирования..."
	docker-compose -f docker-compose.test.yml up -d

# Остановка Docker окружения для тестирования
docker-test-down:
	@echo "🛑 Остановка Docker окружения для тестирования..."
	docker-compose -f docker-compose.test.yml down

# Полная очистка Docker окружения
docker-test-clean:
	@echo "🧹 Очистка Docker окружения для тестирования..."
	docker-compose -f docker-compose.test.yml down -v
	docker system prune -f
