<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

/**
 * Сервис для отправки уведомлений через внешние провайдеры
 * 
 * Демонстрирует использование HTTP клиентов с обработкой ошибок
 */
class NotificationService
{
    private string $apiUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->apiUrl = config('services.notifications.url', 'https://api.notifications.example.com');
        $this->apiKey = config('services.notifications.key', '');
        $this->timeout = config('services.notifications.timeout', 10);
    }

    /**
     * Отправить уведомление через внешний API
     */
    public function sendNotification(array $data): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Laravel-TaskManager/1.0'
                ])
                ->post($this->apiUrl . '/notifications', $data);

            return $this->handleResponse($response);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Notification service connection failed', [
                'error' => $e->getMessage(),
                'url' => $this->apiUrl
            ]);
            
            return [
                'success' => false,
                'error' => 'Connection timeout',
                'retry_after' => 30
            ];
        } catch (\Exception $e) {
            Log::error('Notification service error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return [
                'success' => false,
                'error' => 'Service unavailable',
                'retry_after' => 60
            ];
        }
    }

    /**
     * Отправить уведомление с повторными попытками
     */
    public function sendNotificationWithRetry(array $data, int $maxRetries = 3): array
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json'
                    ])
                    ->post($this->apiUrl . '/notifications', $data);

                $result = $this->handleResponse($response);
                
                if ($result['success']) {
                    return $result;
                }

                if ($response->status() >= 500 && $attempt < $maxRetries) {
                    $waitTime = $attempt * 2; // Экспоненциальная задержка
                    Log::warning("Notification service retry attempt {$attempt}", [
                        'status' => $response->status(),
                        'wait_time' => $waitTime
                    ]);
                    
                    sleep($waitTime);
                    continue;
                }

                if ($response->status() >= 500 && $attempt >= $maxRetries) {
                    return [
                        'success' => false,
                        'error' => 'Max retries exceeded',
                        'status' => $response->status()
                    ];
                }

                return $result;

            } catch (\Exception $e) {
                $lastError = $e;
                
                if ($attempt < $maxRetries) {
                    $waitTime = $attempt * 2;
                    Log::warning("Notification service retry attempt {$attempt}", [
                        'error' => $e->getMessage(),
                        'wait_time' => $waitTime
                    ]);
                    
                    sleep($waitTime);
                }
            }
        }

        return [
            'success' => false,
            'error' => 'Max retries exceeded',
            'last_error' => $lastError?->getMessage()
        ];
    }

    /**
     * Получить статус уведомления
     */
    public function getNotificationStatus(string $notificationId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey
                ])
                ->get($this->apiUrl . '/notifications/' . $notificationId);

            return $this->handleResponse($response);

        } catch (\Exception $e) {
            Log::error('Failed to get notification status', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get status'
            ];
        }
    }

    /**
     * Обработать ответ от внешнего API
     */
    private function handleResponse(Response $response): array
    {
        $status = $response->status();
        
        if ($status >= 200 && $status < 300) {
            return [
                'success' => true,
                'data' => $response->json(),
                'status' => $status
            ];
        }

        if ($status >= 400 && $status < 500) {
            return [
                'success' => false,
                'error' => 'Client error',
                'status' => $status,
                'message' => $response->json('message', 'Bad request')
            ];
        }

        if ($status >= 500) {
            return [
                'success' => false,
                'error' => 'Server error',
                'status' => $status,
                'retry_after' => 30
            ];
        }

        return [
            'success' => false,
            'error' => 'Unknown error',
            'status' => $status
        ];
    }
}

