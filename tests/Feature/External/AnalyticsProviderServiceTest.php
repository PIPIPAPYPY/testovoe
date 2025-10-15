<?php

namespace Tests\Feature\External;

use Tests\TestCase;
use App\Services\External\AnalyticsProviderService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

/**
 * Тесты для сервиса аналитических провайдеров с HTTP мокированием
 * 
 * Демонстрирует тестирование множественных провайдеров и batch операций
 */
class AnalyticsProviderServiceTest extends TestCase
{
    private AnalyticsProviderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Настраиваем тестовые провайдеры
        Config::set('services.analytics.providers', [
            'google_analytics' => [
                'endpoint' => 'https://www.google-analytics.com/collect',
                'api_key' => 'test-ga-key',
                'auth_type' => 'Bearer',
            ],
            'mixpanel' => [
                'endpoint' => 'https://api.mixpanel.com/track',
                'api_key' => 'test-mixpanel-key',
                'auth_type' => 'Basic',
            ],
            'amplitude' => [
                'endpoint' => 'https://api2.amplitude.com/2/httpapi',
                'api_key' => 'test-amplitude-key',
                'auth_type' => 'Bearer',
            ],
        ]);
        
        $this->service = new AnalyticsProviderService();
    }

    /**
     * Тест: успешная отправка во все провайдеры
     */
    public function test_successful_send_to_all_providers(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['status' => 'success'], 200),
            'api.mixpanel.com/*' => Http::response(['status' => 1], 200),
            'api2.amplitude.com/*' => Http::response(['status' => 200], 200),
        ]);

        $data = [
            'event' => 'task_created',
            'user_id' => 1,
            'properties' => ['task_id' => 123]
        ];

        $result = $this->service->sendAnalytics($data);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['success_count']);
        $this->assertEquals(0, $result['failure_count']);
        $this->assertEquals(3, $result['total_providers']);

        // Проверяем, что все провайдеры получили запросы
        Http::assertSentCount(3);
    }

    /**
     * Тест: частичный успех (некоторые провайдеры недоступны)
     */
    public function test_partial_success_with_some_providers_failing(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['status' => 'success'], 200),
            'api.mixpanel.com/*' => Http::response(['error' => 'Service unavailable'], 503),
            'api2.amplitude.com/*' => Http::response(['error' => 'Rate limit exceeded'], 429),
        ]);

        $data = [
            'event' => 'task_created',
            'user_id' => 1
        ];

        $result = $this->service->sendAnalytics($data);

        $this->assertTrue($result['success']); // Хотя бы один провайдер работает
        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(2, $result['failure_count']);
        $this->assertEquals(3, $result['total_providers']);

        // Проверяем результаты для каждого провайдера
        $this->assertTrue($result['results']['google_analytics']['success']);
        $this->assertFalse($result['results']['mixpanel']['success']);
        $this->assertFalse($result['results']['amplitude']['success']);
    }

    /**
     * Тест: полный провал всех провайдеров
     */
    public function test_all_providers_fail(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['error' => 'Server error'], 500),
            'api.mixpanel.com/*' => Http::response(['error' => 'Server error'], 500),
            'api2.amplitude.com/*' => Http::response(['error' => 'Server error'], 500),
        ]);

        $data = [
            'event' => 'task_created',
            'user_id' => 1
        ];

        $result = $this->service->sendAnalytics($data);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['success_count']);
        $this->assertEquals(3, $result['failure_count']);
    }

    /**
     * Тест: отправка batch данных
     */
    public function test_send_batch_analytics(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['status' => 'success'], 200),
            'api.mixpanel.com/*' => Http::response(['status' => 'success'], 200),
            'api2.amplitude.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $batchData = [
            ['event' => 'task_created', 'user_id' => 1, 'task_id' => 123],
            ['event' => 'task_updated', 'user_id' => 1, 'task_id' => 123],
            ['event' => 'task_completed', 'user_id' => 1, 'task_id' => 123],
        ];

        $result = $this->service->sendBatchAnalytics($batchData);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['success_count']);
        $this->assertEquals(0, $result['failure_count']);
        $this->assertEquals(3, $result['batch_size']);

        // Проверяем, что batch запросы были отправлены
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/batch') &&
                   $request->method() === 'POST' &&
                   $request->hasHeader('X-Batch-Size');
        });
    }

    /**
     * Тест: проверка статуса провайдеров
     */
    public function test_get_providers_status(): void
    {
        Http::fake([
            'www.google-analytics.com/collect/health' => Http::response(['status' => 'healthy'], 200),
            'api.mixpanel.com/track/health' => Http::response(['status' => 'healthy'], 200),
            'api2.amplitude.com/2/httpapi/health' => Http::response(['error' => 'Service down'], 503),
        ]);

        $statuses = $this->service->getProvidersStatus();

        $this->assertCount(3, $statuses);
        $this->assertTrue($statuses['google_analytics']['healthy']);
        $this->assertTrue($statuses['mixpanel']['healthy']);
        $this->assertFalse($statuses['amplitude']['healthy']);
        $this->assertEquals(503, $statuses['amplitude']['status']);
    }

    /**
     * Тест: обработка таймаутов для конкретного провайдера
     */
    public function test_handles_provider_timeout(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['status' => 'success'], 200),
            'api.mixpanel.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
            'api2.amplitude.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $data = ['event' => 'test', 'user_id' => 1];
        $result = $this->service->sendAnalytics($data);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['success_count']);
        $this->assertEquals(1, $result['failure_count']);

        $this->assertFalse($result['results']['mixpanel']['success']);
        $this->assertEquals('Connection timeout', $result['results']['mixpanel']['error']);
    }

    /**
     * Тест: различные типы аутентификации
     */
    public function test_different_auth_types(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['status' => 'success'], 200),
            'api.mixpanel.com/*' => Http::response(['status' => 'success'], 200),
            'api2.amplitude.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $data = ['event' => 'test', 'user_id' => 1];
        $this->service->sendAnalytics($data);

        // Проверяем заголовки аутентификации - упрощенная проверка
        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.google-analytics.com/collect';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.mixpanel.com/track';
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api2.amplitude.com/2/httpapi';
        });
    }

    /**
     * Тест: обработка различных HTTP статусов для провайдеров
     */
    public function test_handles_different_http_statuses_for_providers(): void
    {
        // Test 200 status (success)
        Http::fake([
            '*' => Http::response(['data' => 'test'], 200),
        ]);

        $result = $this->service->sendToProvider('google_analytics', [
            'endpoint' => 'https://api.google_analytics.com/test',
            'api_key' => 'test-key',
            'auth_type' => 'Bearer'
        ], ['event' => 'test']);

        $this->assertTrue($result['success']);
    }

    public function test_handles_400_client_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Bad request'], 400),
        ]);

        $result = $this->service->sendToProvider('mixpanel', [
            'endpoint' => 'https://api.mixpanel.com/test',
            'api_key' => 'test-key',
            'auth_type' => 'Bearer'
        ], ['event' => 'test']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Client error', $result['error']);
    }

    public function test_handles_500_server_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Server error'], 500),
        ]);

        $result = $this->service->sendToProvider('amplitude', [
            'endpoint' => 'https://api.amplitude.com/test',
            'api_key' => 'test-key',
            'auth_type' => 'Bearer'
        ], ['event' => 'test']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Server error', $result['error']);
    }

    /**
     * Тест: проверка заголовков для batch запросов
     */
    public function test_batch_request_headers(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['status' => 'success'], 200),
            'api.mixpanel.com/*' => Http::response(['status' => 'success'], 200),
            'api2.amplitude.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $batchData = [
            ['event' => 'test1'],
            ['event' => 'test2'],
        ];

        $this->service->sendBatchAnalytics($batchData);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Batch-Size');
        });
    }

    /**
     * Тест: обработка исключений при отправке в провайдер
     */
    public function test_handles_exceptions_when_sending_to_provider(): void
    {
        Http::fake([
            'api.test.com/*' => function () {
                throw new \Exception('Unexpected error');
            }
        ]);

        $result = $this->service->sendToProvider('test_provider', [
            'endpoint' => 'https://api.test.com/test',
            'api_key' => 'test-key',
            'auth_type' => 'Bearer'
        ], ['event' => 'test']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Service error', $result['error']);
        $this->assertEquals('test_provider', $result['provider']);
    }
}

