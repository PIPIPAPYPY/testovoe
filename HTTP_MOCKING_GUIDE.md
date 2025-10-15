# HTTP Mocking для внешних провайдеров

## Обзор

Реализована система тестирования внешних HTTP интеграций с использованием Laravel HTTP Client mocking и VCR-like fixtures.

## 🎯 Основные возможности

### 1. HTTP Client Mocking (`Http::fake()`)
- Мокирование HTTP запросов к внешним API
- Тестирование различных сценариев ответов
- Проверка заголовков и параметров запросов

### 2. VCR-like Fixtures
- Предзаписанные HTTP ответы для стабильного тестирования
- Воспроизводимые тесты без зависимости от внешних сервисов
- Легкое создание и управление тестовыми данными

### 3. Сценарии ошибок
- 5xx ошибки сервера (500, 502, 503, 504)
- Таймауты соединения
- Частичные успехи (некоторые провайдеры недоступны)
- Rate limiting и другие ограничения

## 📁 Структура файлов

```
app/Services/External/
├── NotificationService.php          # Сервис уведомлений
└── AnalyticsProviderService.php     # Сервис аналитических провайдеров

tests/Feature/External/
├── NotificationServiceTest.php      # Тесты сервиса уведомлений
├── AnalyticsProviderServiceTest.php # Тесты аналитических провайдеров
├── ErrorScenarioTest.php            # Тесты сценариев ошибок
└── VcrFixtureTest.php              # Тесты с VCR-like fixtures

tests/Fixtures/Http/
├── notification_success.json         # Успешный ответ уведомлений
├── notification_500_error.json      # 500 ошибка уведомлений
├── notification_timeout.json        # Таймаут уведомлений
├── analytics_google_success.json    # Успешный ответ Google Analytics
├── analytics_mixpanel_success.json # Успешный ответ Mixpanel
└── analytics_amplitude_error.json   # Ошибка Amplitude

tests/Helpers/
└── HttpFixtureHelper.php            # Helper для работы с fixtures
```

## 🚀 Использование

### Базовое HTTP мокирование

```php
use Illuminate\Support\Facades\Http;

// Мокирование успешного ответа
Http::fake([
    'api.example.com/*' => Http::response(['status' => 'success'], 200)
]);

// Мокирование ошибки
Http::fake([
    'api.example.com/*' => Http::response(['error' => 'Server error'], 500)
]);

// Мокирование таймаута
Http::fake([
    'api.example.com/*' => function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
    }
]);
```

### VCR-like Fixtures

```php
use Tests\Helpers\HttpFixtureHelper;

// Использование fixture
HttpFixtureHelper::fakeWithFixture('notification_success');

// Множественные fixtures
HttpFixtureHelper::fakeWithMultipleFixtures([
    'analytics_google_success',
    'analytics_mixpanel_success',
    'analytics_amplitude_error'
]);

// Проверка соответствия запроса fixture
HttpFixtureHelper::assertRequestMatchesFixture('notification_success');
```

### Тестирование сценариев ошибок

```php
// 5xx ошибки
Http::fake([
    'api.example.com/*' => Http::response(['error' => 'Internal Server Error'], 500)
]);

// Таймауты
Http::fake([
    'api.example.com/*' => function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
    }
]);

// Частичные успехи
Http::fake([
    'provider1.com/*' => Http::response(['status' => 'success'], 200),
    'provider2.com/*' => Http::response(['error' => 'Service unavailable'], 503),
]);
```

## 🧪 Команды тестирования

### Composer команды
```bash
# Все тесты внешних интеграций
composer test:external

# Тесты с HTTP мокированием
composer test:mocking
```

### Make команды
```bash
# Тесты внешних интеграций
make test-external

# Тесты с HTTP мокированием
make test-mocking
```

### PHPUnit команды
```bash
# Конкретная тестовая группа
php artisan test --testsuite=External

# Конкретные тесты
php artisan test --filter="NotificationServiceTest"
```

## 📋 Примеры тестов

### 1. Тест успешной отправки уведомления

```php
public function test_successful_notification_sending(): void
{
    Http::fake([
        'api.notifications.example.com/*' => Http::response([
            'id' => 'notif_123',
            'status' => 'sent'
        ], 200)
    ]);

    $result = $this->service->sendNotification([
        'user_id' => 1,
        'message' => 'Test notification'
    ]);

    $this->assertTrue($result['success']);
    $this->assertEquals(200, $result['status']);
}
```

### 2. Тест обработки 5xx ошибок

```php
public function test_handles_5xx_errors(): void
{
    Http::fake([
        'api.notifications.example.com/*' => Http::response([
            'error' => 'Internal Server Error'
        ], 500)
    ]);

    $result = $this->service->sendNotification($data);

    $this->assertFalse($result['success']);
    $this->assertEquals('Server error', $result['error']);
    $this->assertEquals(30, $result['retry_after']);
}
```

### 3. Тест частичного успеха

```php
public function test_partial_success_with_analytics_providers(): void
{
    Http::fake([
        'provider1.com/*' => Http::response(['status' => 'success'], 200),
        'provider2.com/*' => Http::response(['error' => 'Service unavailable'], 503),
    ]);

    $result = $this->service->sendAnalytics($data);

    $this->assertTrue($result['success']); // Хотя бы один работает
    $this->assertEquals(1, $result['success_count']);
    $this->assertEquals(1, $result['failure_count']);
}
```

## 🔧 Создание Fixtures

### Автоматическое создание

```php
HttpFixtureHelper::createFixtureFromRequest(
    'my_fixture',
    'https://api.example.com/endpoint',
    'POST',
    ['Authorization' => 'Bearer token'],
    ['data' => 'value']
);
```

### Ручное создание

```json
{
  "request": {
    "method": "POST",
    "url": "https://api.example.com/endpoint",
    "headers": {
      "Authorization": "Bearer token",
      "Content-Type": "application/json"
    },
    "body": {
      "data": "value"
    }
  },
  "response": {
    "status": 200,
    "headers": {
      "Content-Type": "application/json"
    },
    "body": {
      "status": "success"
    }
  }
}
```

## 🛠️ Конфигурация

### Настройка внешних сервисов

```php
// config/services.php
'notifications' => [
    'url' => env('NOTIFICATION_SERVICE_URL'),
    'key' => env('NOTIFICATION_SERVICE_KEY'),
    'timeout' => env('NOTIFICATION_SERVICE_TIMEOUT', 10),
],

'analytics' => [
    'providers' => [
        'google_analytics' => [
            'endpoint' => env('GA_ENDPOINT'),
            'api_key' => env('GA_API_KEY'),
            'auth_type' => 'Bearer',
        ],
    ],
],
```

## 📊 Покрытие тестами

### Типы тестируемых сценариев

1. **Успешные операции**
   - Стандартные HTTP ответы (200, 201)
   - Корректная обработка данных
   - Проверка заголовков и параметров

2. **Ошибки клиента (4xx)**
   - 400 Bad Request
   - 401 Unauthorized
   - 403 Forbidden
   - 404 Not Found
   - 429 Rate Limited

3. **Ошибки сервера (5xx)**
   - 500 Internal Server Error
   - 502 Bad Gateway
   - 503 Service Unavailable
   - 504 Gateway Timeout

4. **Сетевые ошибки**
   - Connection timeout
   - DNS resolution failure
   - SSL handshake timeout

5. **Частичные успехи**
   - Некоторые провайдеры недоступны
   - Batch операции с ошибками
   - Retry механизмы

## 🎯 Лучшие практики

### 1. Изоляция тестов
- Каждый тест должен быть независимым
- Очищайте HTTP fake между тестами
- Используйте setUp/tearDown для настройки

### 2. Реалистичные данные
- Используйте реальные форматы API ответов
- Тестируйте с различными типами данных
- Проверяйте граничные случаи

### 3. Проверка запросов
- Убедитесь, что отправляются правильные заголовки
- Проверяйте URL и методы
- Валидируйте тело запроса

### 4. Обработка ошибок
- Тестируйте все возможные сценарии ошибок
- Проверяйте retry логику
- Валидируйте логирование

## 🔍 Отладка

### Просмотр записанных запросов

```php
// После выполнения запросов
Http::assertSent(function ($request) {
    return $request->url() === 'https://api.example.com/endpoint';
});

// Подсчет запросов
Http::assertSentCount(3);
```

### Логирование

```php
// Включение детального логирования
Http::fake([
    'api.example.com/*' => Http::response(['status' => 'success'], 200)
])->dump();
```

## 📈 Мониторинг

### Метрики тестирования
- Время выполнения HTTP запросов
- Количество успешных/неуспешных запросов
- Retry статистика
- Ошибки по типам

### Алерты
- Превышение таймаутов
- Высокий процент ошибок
- Недоступность внешних сервисов

## 🚀 CI/CD интеграция

### GitHub Actions
```yaml
- name: Run External Integration Tests
  run: |
    composer test:external
    composer test:mocking
```

### Локальная разработка
```bash
# Быстрые тесты
make test-mocking

# Полные тесты
make test-external
```

## 📚 Дополнительные ресурсы

- [Laravel HTTP Client Documentation](https://laravel.com/docs/http-client)
- [PHPUnit HTTP Testing](https://phpunit.readthedocs.io/en/stable/test-doubles.html)
- [VCR Ruby Gem](https://github.com/vcr/vcr) - вдохновение для fixtures
- [HTTP Mocking Best Practices](https://docs.pact.io/)

---

Эта система обеспечивает надежное тестирование внешних интеграций с полным покрытием различных сценариев ошибок и успешных операций.



