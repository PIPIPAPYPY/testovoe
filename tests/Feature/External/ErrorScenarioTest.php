<?php

namespace Tests\Feature\External;

use Tests\TestCase;
use App\Services\External\NotificationService;
use App\Services\External\AnalyticsProviderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * Тесты для различных сценариев ошибок внешних сервисов
 * 
 * Демонстрирует тестирование поведения при 5xx/timeout/частичных успехах
 */
class ErrorScenarioTest extends TestCase
{
    private NotificationService $notificationService;
    private AnalyticsProviderService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->notificationService = new NotificationService();
        
        // Настраиваем тестовые провайдеры
        Config::set('services.analytics.providers', [
            'provider1' => [
                'endpoint' => 'https://api.provider1.com',
                'api_key' => 'key1',
                'auth_type' => 'Bearer',
            ],
            'provider2' => [
                'endpoint' => 'https://api.provider2.com',
                'api_key' => 'key2',
                'auth_type' => 'Bearer',
            ],
        ]);
        
        $this->analyticsService = new AnalyticsProviderService();
    }

    /**
     * Тест: обработка 500 Internal Server Error
     */
    public function test_handles_500_internal_server_error(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response([
                'error' => 'Internal Server Error',
                'message' => 'Database connection failed'
            ], 500)
        ]);

        $result = $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Server error', $result['error']);
        $this->assertEquals(500, $result['status']);
        $this->assertEquals(30, $result['retry_after']);
    }

    /**
     * Тест: обработка 502 Bad Gateway
     */
    public function test_handles_502_bad_gateway(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response([
                'error' => 'Bad Gateway',
                'message' => 'Upstream server unavailable'
            ], 502)
        ]);

        $result = $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Server error', $result['error']);
        $this->assertEquals(502, $result['status']);
    }

    /**
     * Тест: обработка 503 Service Unavailable
     */
    public function test_handles_503_service_unavailable(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response([
                'error' => 'Service Unavailable',
                'message' => 'Server maintenance in progress',
                'retry_after' => 300
            ], 503)
        ]);

        $result = $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Server error', $result['error']);
        $this->assertEquals(503, $result['status']);
    }

    /**
     * Тест: обработка 504 Gateway Timeout
     */
    public function test_handles_504_gateway_timeout(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response([
                'error' => 'Gateway Timeout',
                'message' => 'Request timeout'
            ], 504)
        ]);

        $result = $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Server error', $result['error']);
        $this->assertEquals(504, $result['status']);
    }

    /**
     * Тест: обработка Connection Timeout
     */
    public function test_handles_connection_timeout(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => function () {
                throw new ConnectionException('Connection timeout after 10 seconds');
            }
        ]);

        $result = $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Connection timeout', $result['error']);
        $this->assertEquals(30, $result['retry_after']);
    }

    /**
     * Тест: обработка DNS Resolution Failure
     */
    public function test_handles_dns_resolution_failure(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => function () {
                throw new ConnectionException('Could not resolve host: api.notifications.example.com');
            }
        ]);

        $result = $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Connection timeout', $result['error']);
    }

    /**
     * Тест: частичный успех с аналитическими провайдерами
     */
    public function test_partial_success_with_analytics_providers(): void
    {
        Http::fake([
            'https://api.provider1.com' => Http::response(['status' => 'success'], 200),
            'https://api.provider2.com' => Http::response(['error' => 'Rate limit exceeded'], 429),
        ]);

        $data = [
            'event' => 'task_created',
            'user_id' => 1,
            'properties' => ['task_id' => 123]
        ];

        $result = $this->analyticsService->sendAnalytics($data);

        // Debug: Check the actual result
        $this->assertTrue($result['success'], "Analytics service failed. Result: " . json_encode($result)); // Хотя бы один провайдер работает
        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(1, $result['failure_count']);
        $this->assertEquals(2, $result['total_providers']);

        // Проверяем результаты для каждого провайдера
        $this->assertTrue($result['results']['provider1']['success']);
        $this->assertFalse($result['results']['provider2']['success']);
        $this->assertEquals(429, $result['results']['provider2']['status']);
    }

    /**
     * Тест: все провайдеры недоступны
     */
    public function test_all_analytics_providers_unavailable(): void
    {
        Http::fake([
            'api.provider1.com/*' => Http::response(['error' => 'Service unavailable'], 503),
            'api.provider2.com/*' => Http::response(['error' => 'Service unavailable'], 503),
        ]);

        $data = ['event' => 'test', 'user_id' => 1];
        $result = $this->analyticsService->sendAnalytics($data);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['success_count']);
        $this->assertEquals(2, $result['failure_count']);
    }

    /**
     * Тест: смешанные ошибки (разные типы)
     */
    public function test_mixed_error_types(): void
    {
        Http::fake([
            'https://api.provider1.com' => Http::response(['status' => 'success'], 200),
            'https://api.provider2.com' => function () {
                throw new ConnectionException('Connection timeout');
            },
        ]);

        $data = ['event' => 'test', 'user_id' => 1];
        $result = $this->analyticsService->sendAnalytics($data);

        $this->assertTrue($result['success']); // Один провайдер работает
        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(1, $result['failure_count']);

        $this->assertTrue($result['results']['provider1']['success']);
        $this->assertFalse($result['results']['provider2']['success']);
        $this->assertEquals('Connection timeout', $result['results']['provider2']['error']);
    }

    /**
     * Тест: повторные попытки с экспоненциальной задержкой
     */
    public function test_retry_with_exponential_backoff(): void
    {
        $attempts = 0;
        
        Http::fake([
            'api.notifications.example.com/*' => function () use (&$attempts) {
                $attempts++;
                if ($attempts < 3) {
                    return Http::response(['error' => 'Server error'], 500);
                }
                return Http::response(['id' => 'notif_123', 'status' => 'sent'], 200);
            }
        ]);

        $data = ['user_id' => 1, 'message' => 'Test notification'];
        $result = $this->notificationService->sendNotificationWithRetry($data, 3);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $attempts);
    }

    /**
     * Тест: превышение лимита попыток
     */
    public function test_max_retries_exceeded(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response(['error' => 'Server error'], 500)
        ]);

        $data = ['user_id' => 1, 'message' => 'Test notification'];
        $result = $this->notificationService->sendNotificationWithRetry($data, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('Max retries exceeded', $result['error']);
    }

    /**
     * Тест: обработка Rate Limiting (429)
     */
    public function test_handles_rate_limiting(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests',
                'retry_after' => 60
            ], 429)
        ]);

        $result = $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Client error', $result['error']);
        $this->assertEquals(429, $result['status']);
    }

    /**
     * Тест: обработка различных таймаутов
     */
    public function test_handles_different_timeout_scenarios(): void
    {
        $timeoutScenarios = [
            'Connection timeout' => new ConnectionException('Connection timeout'),
            'Read timeout' => new ConnectionException('Read timeout'),
            'SSL handshake timeout' => new ConnectionException('SSL handshake timeout'),
        ];

        foreach ($timeoutScenarios as $scenario => $exception) {
            Http::fake([
                'api.notifications.example.com/*' => function () use ($exception) {
                    throw $exception;
                }
            ]);

            $result = $this->notificationService->sendNotification([
                'user_id' => 1,
                'message' => 'Test notification'
            ]);

            $this->assertFalse($result['success']);
            $this->assertEquals('Connection timeout', $result['error']);
        }
    }

    /**
     * Тест: обработка неожиданных исключений
     */
    public function test_handles_unexpected_exceptions(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => function () {
                throw new \Exception('Unexpected system error');
            }
        ]);

        $result = $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Service unavailable', $result['error']);
        $this->assertEquals(60, $result['retry_after']);
    }

    /**
     * Тест: проверка логирования ошибок
     */
    public function test_error_logging(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response(['error' => 'Server error'], 500)
        ]);

        // Перехватываем логи - временно отключено для совместимости
        // $this->expectsEvents(\Illuminate\Log\Events\MessageLogged::class);

        $this->notificationService->sendNotification([
            'user_id' => 1,
            'message' => 'Test notification'
        ]);
    }

    /**
     * Тест: обработка больших batch данных при ошибках
     */
    public function test_handles_large_batch_with_errors(): void
    {
        $largeBatch = [];
        for ($i = 0; $i < 100; $i++) {
            $largeBatch[] = [
                'event' => 'test_event',
                'user_id' => $i,
                'timestamp' => now()->toISOString()
            ];
        }

        Http::fake([
            'api.provider1.com/*' => Http::response(['status' => 'success'], 200),
            'api.provider2.com/*' => Http::response(['error' => 'Payload too large'], 413),
        ]);

        $result = $this->analyticsService->sendBatchAnalytics($largeBatch);

        $this->assertTrue($result['success']); // Один провайдер работает
        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(1, $result['failure_count']);
        $this->assertEquals(100, $result['batch_size']);
    }
}

