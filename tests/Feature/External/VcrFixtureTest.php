<?php

namespace Tests\Feature\External;

use Tests\TestCase;
use Tests\Helpers\HttpFixtureHelper;
use App\Services\External\NotificationService;
use App\Services\External\AnalyticsProviderService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/**
 * Тесты с использованием VCR-like fixtures
 * 
 * Демонстрирует использование предзаписанных HTTP ответов для тестирования
 */
class VcrFixtureTest extends TestCase
{
    private NotificationService $notificationService;
    private AnalyticsProviderService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->notificationService = new NotificationService();
        
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
        
        $this->analyticsService = new AnalyticsProviderService();
    }

    /**
     * Тест: успешная отправка уведомления с использованием fixture
     */
    public function test_notification_success_with_fixture(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'notif_123456',
                'status' => 'sent',
                'delivered_at' => '2024-01-01T12:00:00Z',
                'provider' => 'email'
            ], 200)
        ]);

        $data = [
            'user_id' => 1,
            'message' => 'Test notification',
            'type' => 'task_reminder'
        ];

        $result = $this->notificationService->sendNotification($data);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
        $this->assertEquals('notif_123456', $result['data']['id']);
        $this->assertEquals('sent', $result['data']['status']);

        // Проверяем, что запрос был сделан
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.notifications.example.com/notifications';
        });
    }

    /**
     * Тест: обработка 500 ошибки с использованием fixture
     */
    public function test_notification_500_error_with_fixture(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Server error'], 500)
        ]);

        $data = [
            'user_id' => 1,
            'message' => 'Test notification'
        ];

        $result = $this->notificationService->sendNotification($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Server error', $result['error']);
        $this->assertEquals(500, $result['status']);
        $this->assertEquals(30, $result['retry_after']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.notifications.example.com/notifications';
        });
    }

    /**
     * Тест: обработка таймаута с использованием fixture
     */
    public function test_notification_timeout_with_fixture(): void
    {
        Http::fake([
            '*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            }
        ]);

        $data = [
            'user_id' => 1,
            'message' => 'Test notification'
        ];

        $result = $this->notificationService->sendNotification($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Connection timeout', $result['error']);
        $this->assertEquals(30, $result['retry_after']);

        // Note: HTTP assertion removed because connection exceptions prevent request recording
    }

    /**
     * Тест: аналитические провайдеры с использованием fixtures
     */
    public function test_analytics_providers_with_fixtures(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['status' => 'success'], 200),
            'api.mixpanel.com/*' => Http::response(['status' => 'success'], 200),
            'api2.amplitude.com/*' => Http::response(['error' => 'Rate limit exceeded'], 429),
        ]);

        $data = [
            'event' => 'task_created',
            'user_id' => 1,
            'properties' => ['task_id' => 123]
        ];

        $result = $this->analyticsService->sendAnalytics($data);

        $this->assertTrue($result['success']); // Хотя бы один провайдер работает
        $this->assertEquals(2, $result['success_count']); // Google Analytics и Mixpanel
        $this->assertEquals(1, $result['failure_count']); // Amplitude
        $this->assertEquals(3, $result['total_providers']);

        // Проверяем результаты для каждого провайдера
        $this->assertTrue($result['results']['google_analytics']['success']);
        $this->assertTrue($result['results']['mixpanel']['success']);
        $this->assertFalse($result['results']['amplitude']['success']);
        $this->assertEquals(429, $result['results']['amplitude']['status']);
    }

    /**
     * Тест: batch аналитика с fixtures
     */
    public function test_batch_analytics_with_fixtures(): void
    {
        Http::fake([
            'www.google-analytics.com/*' => Http::response(['status' => 'success'], 200),
            'api.mixpanel.com/*' => Http::response(['status' => 'success'], 200),
            'api2.amplitude.com/*' => Http::response(['error' => 'Rate limit exceeded'], 429),
        ]);

        $batchData = [
            ['event' => 'task_created', 'user_id' => 1, 'task_id' => 123],
            ['event' => 'task_updated', 'user_id' => 1, 'task_id' => 123],
            ['event' => 'task_completed', 'user_id' => 1, 'task_id' => 123],
        ];

        $result = $this->analyticsService->sendBatchAnalytics($batchData);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['success_count']);
        $this->assertEquals(1, $result['failure_count']);
        $this->assertEquals(3, $result['batch_size']);
    }

    /**
     * Тест: проверка валидности fixtures
     */
    public function test_fixture_validation(): void
    {
        $fixtures = [
            'notification_success',
            'notification_500_error',
            'notification_timeout',
            'analytics_google_success',
            'analytics_mixpanel_success',
            'analytics_amplitude_error'
        ];

        foreach ($fixtures as $fixture) {
            $this->assertTrue(
                HttpFixtureHelper::validateFixture($fixture),
                "Fixture {$fixture} is not valid"
            );
        }
    }

    /**
     * Тест: получение списка доступных fixtures
     */
    public function test_get_available_fixtures(): void
    {
        $fixtures = HttpFixtureHelper::getAvailableFixtures();
        
        $this->assertIsArray($fixtures);
        $this->assertContains('notification_success', $fixtures);
        $this->assertContains('notification_500_error', $fixtures);
        $this->assertContains('analytics_google_success', $fixtures);
    }

    /**
     * Тест: создание fixture из запроса
     */
    public function test_create_fixture_from_request(): void
    {
        $fixtureName = 'test_fixture_' . time();
        
        HttpFixtureHelper::createFixtureFromRequest(
            $fixtureName,
            'https://api.test.com/endpoint',
            'POST',
            [
                'Authorization' => 'Bearer test-key',
                'Content-Type' => 'application/json'
            ],
            [
                'test' => 'data',
                'user_id' => 123
            ]
        );

        $this->assertTrue(HttpFixtureHelper::validateFixture($fixtureName));
        
        // Очищаем созданный fixture
        $filePath = base_path('tests/Fixtures/Http/' . $fixtureName . '.json');
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Тест: очистка кэша fixtures
     */
    public function test_clear_fixtures_cache(): void
    {
        // Загружаем fixture
        HttpFixtureHelper::loadFixture('notification_success');
        
        // Очищаем кэш
        HttpFixtureHelper::clearFixturesCache();
        
        // Проверяем, что кэш очищен (fixture должен загрузиться заново)
        $fixture = HttpFixtureHelper::loadFixture('notification_success');
        $this->assertIsArray($fixture);
    }

    /**
     * Тест: обработка несуществующего fixture
     */
    public function test_handles_missing_fixture(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fixture non_existent_fixture not found');
        
        HttpFixtureHelper::loadFixture('non_existent_fixture');
    }

    /**
     * Тест: проверка соответствия запроса fixture с фильтром
     */
    public function test_assert_request_matches_fixture_with_filter(): void
    {
        Http::fake([
            '*' => Http::response([
                'id' => 'notif_123456',
                'status' => 'sent',
                'delivered_at' => '2024-01-01T12:00:00Z',
                'provider' => 'email'
            ], 200)
        ]);

        $data = [
            'user_id' => 1,
            'message' => 'Test notification',
            'type' => 'task_reminder'
        ];

        $this->notificationService->sendNotification($data);

        // Проверяем с дополнительным фильтром
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization') &&
                   $request->hasHeader('Content-Type');
        });
    }

    /**
     * Тест: комплексный сценарий с множественными fixtures
     */
    public function test_complex_scenario_with_multiple_fixtures(): void
    {
        // Настраиваем разные ответы для разных провайдеров
        HttpFixtureHelper::fakeWithMultipleFixtures([
            'analytics_google_success',    // Успех
            'analytics_mixpanel_success', // Успех  
            'analytics_amplitude_error'   // Ошибка 429
        ]);

        $data = [
            'event' => 'task_created',
            'user_id' => 1,
            'properties' => [
                'task_id' => 123,
                'priority' => 'high',
                'category' => 'work'
            ]
        ];

        $result = $this->analyticsService->sendAnalytics($data);

        // Проверяем общий результат
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['success_count']);
        $this->assertEquals(1, $result['failure_count']);

        // Проверяем детали для каждого провайдера
        $this->assertTrue($result['results']['google_analytics']['success']);
        $this->assertTrue($result['results']['mixpanel']['success']);
        $this->assertFalse($result['results']['amplitude']['success']);
        $this->assertEquals(429, $result['results']['amplitude']['status']);
    }
}

