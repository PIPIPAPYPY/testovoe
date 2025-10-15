<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;

/**
 * Сервис для отправки аналитических данных во внешние провайдеры
 * 
 * Демонстрирует работу с множественными провайдерами и batch операциями
 */
class AnalyticsProviderService
{
    private array $providers;
    private int $timeout;

    public function __construct()
    {
        $this->providers = config('services.analytics.providers', []);
        $this->timeout = config('services.analytics.timeout', 15);
    }

    /**
     * Отправить аналитические данные во все провайдеры
     */
    public function sendAnalytics(array $data): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($this->providers as $providerName => $config) {
            try {
                $result = $this->sendToProvider($providerName, $config, $data);
                $results[$providerName] = $result;
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }

            } catch (\Exception $e) {
                $results[$providerName] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        return [
            'success' => $successCount > 0,
            'results' => $results,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'total_providers' => count($this->providers)
        ];
    }

    /**
     * Отправить данные в конкретный провайдер
     */
    public function sendToProvider(string $providerName, array $config, array $data): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $config['auth_type'] . ' ' . $config['api_key'],
                    'Content-Type' => 'application/json',
                    'X-Provider' => $providerName
                ])
                ->post($config['endpoint'], $data);

            return $this->handleProviderResponse($response, $providerName);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Analytics provider {$providerName} connection failed", [
                'error' => $e->getMessage(),
                'endpoint' => $config['endpoint']
            ]);

            return [
                'success' => false,
                'error' => 'Connection timeout',
                'provider' => $providerName
            ];
        } catch (\Exception $e) {
            Log::error("Analytics provider {$providerName} error", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => 'Service error',
                'provider' => $providerName
            ];
        }
    }

    /**
     * Отправить batch данные
     */
    public function sendBatchAnalytics(array $batchData): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($this->providers as $providerName => $config) {
            try {
                $result = $this->sendBatchToProvider($providerName, $config, $batchData);
                $results[$providerName] = $result;
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }

            } catch (\Exception $e) {
                $results[$providerName] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
        }

        return [
            'success' => $successCount > 0,
            'results' => $results,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'batch_size' => count($batchData)
        ];
    }

    /**
     * Отправить batch данные в конкретный провайдер
     */
    private function sendBatchToProvider(string $providerName, array $config, array $batchData): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $config['auth_type'] . ' ' . $config['api_key'],
                    'Content-Type' => 'application/json',
                    'X-Provider' => $providerName,
                    'X-Batch-Size' => count($batchData)
                ])
                ->post($config['endpoint'] . '/batch', [
                    'events' => $batchData,
                    'timestamp' => now()->toISOString()
                ]);

            return $this->handleProviderResponse($response, $providerName);

        } catch (\Exception $e) {
            Log::error("Analytics provider {$providerName} batch error", [
                'error' => $e->getMessage(),
                'batch_size' => count($batchData)
            ]);

            return [
                'success' => false,
                'error' => 'Batch processing failed',
                'provider' => $providerName
            ];
        }
    }

    /**
     * Получить статус провайдеров
     */
    public function getProvidersStatus(): array
    {
        $statuses = [];

        foreach ($this->providers as $providerName => $config) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'Authorization' => $config['auth_type'] . ' ' . $config['api_key']
                    ])
                    ->get($config['endpoint'] . '/health');

                $statuses[$providerName] = [
                    'status' => $response->status(),
                    'healthy' => $response->status() === 200,
                    'response_time' => $response->transferStats?->getHandlerStat('total_time') ?? 0
                ];

            } catch (\Exception $e) {
                $statuses[$providerName] = [
                    'status' => 'error',
                    'healthy' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $statuses;
    }

    /**
     * Обработать ответ от провайдера
     */
    private function handleProviderResponse(Response $response, string $providerName): array
    {
        $status = $response->status();
        
        if ($status >= 200 && $status < 300) {
            return [
                'success' => true,
                'data' => $response->json(),
                'status' => $status,
                'provider' => $providerName
            ];
        }

        if ($status >= 400 && $status < 500) {
            return [
                'success' => false,
                'error' => 'Client error',
                'status' => $status,
                'provider' => $providerName,
                'message' => $response->json('message', 'Bad request')
            ];
        }

        if ($status >= 500) {
            return [
                'success' => false,
                'error' => 'Server error',
                'status' => $status,
                'provider' => $providerName,
                'retry_after' => 30
            ];
        }

        return [
            'success' => false,
            'error' => 'Unknown error',
            'status' => $status,
            'provider' => $providerName
        ];
    }
}



