<?php

namespace Tests\Feature\External;

use Tests\TestCase;
use App\Services\External\NotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;

/**
 * Тесты для сервиса уведомлений с HTTP мокированием
 * 
 * Демонстрирует использование Http::fake() для тестирования внешних API
 */
class NotificationServiceTest extends TestCase
{
    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
    }

    /**
     * Тест: успешная отправка уведомления
     */
    public function test_successful_notification_sending(): void
    {
        // Мокируем успешный ответ
        Http::fake([
            '*' => Http::response([
                'id' => 'notif_123',
                'status' => 'sent',
                'delivered_at' => '2024-01-01T12:00:00Z'
            ], 200)
        ]);

        $data = [
            'user_id' => 1,
            'message' => 'Test notification',
            'type' => 'task_reminder'
        ];

        $result = $this->service->sendNotification($data);

        // Debug: Check if the service was called
        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('notif_123', $result['data']['id']);

        // Проверяем, что HTTP запрос был сделан - упрощенная проверка
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.notifications.example.com/notifications';
        });
    }

    /**
     * Тест: обработка 4xx ошибок
     */
    public function test_handles_4xx_errors(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response([
                'error' => 'Invalid request',
                'message' => 'Missing required field: user_id'
            ], 400)
        ]);

        $data = [
            'message' => 'Test notification'
        ];

        $result = $this->service->sendNotification($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Client error', $result['error']);
        $this->assertEquals(400, $result['status']);
        $this->assertEquals('Missing required field: user_id', $result['message']);
    }

    /**
     * Тест: обработка 5xx ошибок
     */
    public function test_handles_5xx_errors(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response([
                'error' => 'Internal server error'
            ], 500)
        ]);

        $data = [
            'user_id' => 1,
            'message' => 'Test notification'
        ];

        $result = $this->service->sendNotification($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Server error', $result['error']);
        $this->assertEquals(500, $result['status']);
        $this->assertEquals(30, $result['retry_after']);
    }

    /**
     * Тест: обработка таймаутов
     */
    public function test_handles_connection_timeout(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            }
        ]);

        $data = [
            'user_id' => 1,
            'message' => 'Test notification'
        ];

        $result = $this->service->sendNotification($data);

        $this->assertFalse($result['success']);
        $this->assertEquals('Connection timeout', $result['error']);
        $this->assertEquals(30, $result['retry_after']);
    }

    /**
     * Тест: повторные попытки при 5xx ошибках
     */
    public function test_retry_mechanism_for_5xx_errors(): void
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

        $data = [
            'user_id' => 1,
            'message' => 'Test notification'
        ];

        $result = $this->service->sendNotificationWithRetry($data, 3);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $attempts);
    }

    /**
     * Тест: превышение максимального количества попыток
     */
    public function test_max_retries_exceeded(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response(['error' => 'Server error'], 500)
        ]);

        $data = [
            'user_id' => 1,
            'message' => 'Test notification'
        ];

        $result = $this->service->sendNotificationWithRetry($data, 2);

        $this->assertFalse($result['success']);
        $this->assertEquals('Max retries exceeded', $result['error']);
    }

    /**
     * Тест: получение статуса уведомления
     */
    public function test_get_notification_status(): void
    {
        Http::fake([
            'api.notifications.example.com/notifications/notif_123' => Http::response([
                'id' => 'notif_123',
                'status' => 'delivered',
                'delivered_at' => '2024-01-01T12:00:00Z'
            ], 200)
        ]);

        $result = $this->service->getNotificationStatus('notif_123');

        $this->assertTrue($result['success']);
        $this->assertEquals('delivered', $result['data']['status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.notifications.example.com/notifications/notif_123' &&
                   $request->method() === 'GET';
        });
    }

    /**
     * Тест: обработка различных HTTP статусов
     */
    public function test_handles_different_http_statuses(): void
    {
        // Test 200 status (success)
        Http::fake([
            '*' => Http::response(['data' => 'test'], 200)
        ]);

        $result = $this->service->sendNotification(['user_id' => 1, 'message' => 'test']);
        $this->assertTrue($result['success']);
    }

    public function test_handles_400_client_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Bad request'], 400)
        ]);

        $result = $this->service->sendNotification(['user_id' => 1, 'message' => 'test']);
        $this->assertFalse($result['success']);
        $this->assertEquals('Client error', $result['error']);
    }

    public function test_handles_500_server_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Server error'], 500)
        ]);

        $result = $this->service->sendNotification(['user_id' => 1, 'message' => 'test']);
        $this->assertFalse($result['success']);
        $this->assertEquals('Server error', $result['error']);
    }

    /**
     * Тест: проверка заголовков запроса
     */
    public function test_request_headers_are_correct(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => Http::response(['id' => 'test'], 200)
        ]);

        $this->service->sendNotification(['user_id' => 1, 'message' => 'test']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization');
        });
    }

    /**
     * Тест: обработка неожиданных исключений
     */
    public function test_handles_unexpected_exceptions(): void
    {
        Http::fake([
            'api.notifications.example.com/*' => function () {
                throw new \Exception('Unexpected error');
            }
        ]);

        $result = $this->service->sendNotification(['user_id' => 1, 'message' => 'test']);

        $this->assertFalse($result['success']);
        $this->assertEquals('Service unavailable', $result['error']);
        $this->assertEquals(60, $result['retry_after']);
    }
}

