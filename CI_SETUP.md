# Настройка CI для тестирования PostgreSQL

## Обзор

Настроена многоуровневая система тестирования для выявления багов, которые проявляются только на продакшн базе данных (PostgreSQL).

## Архитектура тестирования

### 1. Быстрые тесты (PR)
- **Цель**: Быстрая обратная связь для разработчиков
- **База данных**: SQLite
- **Тесты**: Unit + Smoke Feature
- **Время выполнения**: ~2-3 минуты
- **Триггер**: Pull Request

### 2. Полные тесты (main)
- **Цель**: Полная проверка совместимости
- **База данных**: PostgreSQL (13, 14, 15)
- **Тесты**: Все тесты + PostgreSQL совместимость
- **Время выполнения**: ~10-15 минут
- **Триггер**: Push в main

### 3. Nightly тесты
- **Цель**: Глубокая проверка на всех версиях
- **База данных**: PostgreSQL (13, 14, 15, 16)
- **Тесты**: Полная матрица + покрытие кода
- **Время выполнения**: ~30-45 минут
- **Триггер**: Ежедневно в 2:00 UTC

## Файлы конфигурации

### GitHub Actions
- `.github/workflows/ci.yml` - Основные тесты
- `.github/workflows/nightly.yml` - Nightly тесты

### PHPUnit
- `phpunit.xml` - Конфигурация тестов с поддержкой PostgreSQL

### Docker
- `docker-compose.test.yml` - Контейнеры PostgreSQL для локального тестирования

### Скрипты
- `scripts/test-quick.sh` - Быстрые тесты
- `scripts/test-postgresql.sh` - Тесты на PostgreSQL
- `scripts/test-all-postgresql.sh` - Тесты всех версий PostgreSQL

### Makefile
- `Makefile` - Удобные команды для тестирования

## Локальное тестирование

### Быстрые тесты
```bash
# Все тесты на SQLite
make test

# Только быстрые тесты для PR
make test-quick
```

### Тестирование PostgreSQL
```bash
# Локальный PostgreSQL
make test-postgresql

# Все версии PostgreSQL через Docker
make test-all-postgresql
```

### Тесты производительности
```bash
make test-performance
```

## CI/CD конфигурация

### Для Pull Requests
```yaml
# Быстрые тесты на SQLite
quick-tests:
  runs-on: ubuntu-latest
  if: github.event_name == 'pull_request'
  strategy:
    matrix:
      php-version: [8.2, 8.3]
```

### Для main ветки
```yaml
# Полные тесты на PostgreSQL
full-tests-postgresql:
  runs-on: ubuntu-latest
  if: github.ref == 'refs/heads/main'
  strategy:
    matrix:
      php-version: [8.2, 8.3]
      postgres-version: [13, 14, 15]
```

### Nightly тесты
```yaml
# Полная матрица тестов
nightly-tests:
  runs-on: ubuntu-latest
  if: github.event_name == 'schedule'
  strategy:
    matrix:
      php-version: [8.2, 8.3]
      postgres-version: [13, 14, 15, 16]
```

## Переменные окружения

### SQLite (по умолчанию)
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/testing.sqlite
```

### PostgreSQL
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=testing
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

## Структура тестов

### Unit тесты (`tests/Unit/`)
- Быстрые, изолированные
- Не требуют базы данных
- Тестируют отдельные классы

### Feature тесты (`tests/Feature/`)
- Интеграционные тесты
- Требуют базы данных
- Тестируют полные сценарии

### PostgreSQL совместимость (`tests/Feature/PostgreSQLCompatibilityTest.php`)
- Специальные тесты для PostgreSQL
- Проверяют типы данных
- Тестируют специфичные функции

### Тесты производительности (`tests/Feature/Performance/`)
- Тестируют производительность
- Проверяют использование индексов
- Тестируют память и время

## Команды Composer

```bash
# Стандартные тесты
composer test

# Быстрые тесты для PR
composer test:quick

# Тесты на PostgreSQL
composer test:postgresql

# Тесты производительности
composer test:performance

# Тесты с покрытием
composer test:coverage
```

## Мониторинг и алерты

### Метрики
- Время выполнения тестов
- Использование памяти
- Покрытие кода
- Количество тестов

### Алерты
- Тесты падают на PostgreSQL
- Медленные тесты (> 30 секунд)
- Низкое покрытие кода (< 80%)
- Проблемы с производительностью

## Преимущества

1. **Быстрая обратная связь** для разработчиков
2. **Высокое качество** кода
3. **Совместимость** с продакшн базой данных
4. **Производительность** приложения
5. **Выявление багов** на раннем этапе

## Заключение

Эта система тестирования обеспечивает:

- **Быстрые тесты** для PR (SQLite)
- **Полные тесты** для main (PostgreSQL)
- **Nightly тесты** для глубокой проверки
- **Локальное тестирование** всех сценариев
- **Автоматизацию** через CI/CD

Это закрывает класс багов, которые проявляются только на продакшн базе данных, обеспечивая высокое качество кода и стабильность приложения.



