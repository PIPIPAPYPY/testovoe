<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\ConnectionException;

/**
 * Helper для работы с HTTP fixtures (VCR-like функциональность)
 * 
 * Позволяет использовать предзаписанные ответы для тестирования
 */
class HttpFixtureHelper
{
    private static array $fixtures = [];
    private static string $fixturesPath = 'tests/Fixtures/Http/';

    /**
     * Загрузить fixture из файла
     */
    public static function loadFixture(string $fixtureName): array
    {
        if (!isset(self::$fixtures[$fixtureName])) {
            $filePath = base_path(self::$fixturesPath . $fixtureName . '.json');
            
            if (!file_exists($filePath)) {
                throw new \InvalidArgumentException("Fixture {$fixtureName} not found");
            }
            
            $content = file_get_contents($filePath);
            self::$fixtures[$fixtureName] = json_decode($content, true);
        }
        
        return self::$fixtures[$fixtureName];
    }

    /**
     * Настроить HTTP fake с использованием fixture
     */
    public static function fakeWithFixture(string $fixtureName): void
    {
        $fixture = self::loadFixture($fixtureName);
        
        $url = $fixture['request']['url'];
        $method = $fixture['request']['method'];
        
        if (isset($fixture['response']['error'])) {
            // Обработка ошибок (timeout, connection errors)
            Http::fake([
                $url => function () use ($fixture) {
                    $errorType = $fixture['response']['error']['type'] ?? 'ConnectionException';
                    $errorMessage = $fixture['response']['error']['message'] ?? 'Connection failed';
                    
                    if ($errorType === 'ConnectionException') {
                        throw new ConnectionException($errorMessage);
                    }
                    
                    throw new \Exception($errorMessage);
                }
            ]);
        } else {
            // Обработка обычных ответов
            Http::fake([
                $url => Http::response(
                    $fixture['response']['body'],
                    $fixture['response']['status'],
                    $fixture['response']['headers'] ?? []
                )
            ]);
        }
    }

    /**
     * Настроить HTTP fake с множественными fixtures
     */
    public static function fakeWithMultipleFixtures(array $fixtures): void
    {
        $fakeResponses = [];
        
        foreach ($fixtures as $fixtureName) {
            $fixture = self::loadFixture($fixtureName);
            $url = $fixture['request']['url'];
            
            if (isset($fixture['response']['error'])) {
                $fakeResponses[$url] = function () use ($fixture) {
                    $errorType = $fixture['response']['error']['type'] ?? 'ConnectionException';
                    $errorMessage = $fixture['response']['error']['message'] ?? 'Connection failed';
                    
                    if ($errorType === 'ConnectionException') {
                        throw new ConnectionException($errorMessage);
                    }
                    
                    throw new \Exception($errorMessage);
                };
            } else {
                $fakeResponses[$url] = Http::response(
                    $fixture['response']['body'],
                    $fixture['response']['status'],
                    $fixture['response']['headers'] ?? []
                );
            }
        }
        
        Http::fake($fakeResponses);
    }

    /**
     * Проверить, что запрос соответствует fixture
     */
    public static function assertRequestMatchesFixture(string $fixtureName, callable $requestFilter = null): void
    {
        $fixture = self::loadFixture($fixtureName);
        $expectedRequest = $fixture['request'];
        
        Http::assertSent(function ($request) use ($expectedRequest, $requestFilter) {
            // Проверяем URL
            if ($request->url() !== $expectedRequest['url']) {
                return false;
            }
            
            // Проверяем метод
            if ($request->method() !== $expectedRequest['method']) {
                return false;
            }
            
            // Проверяем заголовки
            foreach ($expectedRequest['headers'] as $headerName => $expectedValue) {
                if ($request->header($headerName) !== $expectedValue) {
                    return false;
                }
            }
            
            // Проверяем тело запроса
            if (isset($expectedRequest['body'])) {
                $requestBody = $request->data();
                $expectedBody = $expectedRequest['body'];
                
                if ($requestBody !== $expectedBody) {
                    return false;
                }
            }
            
            // Дополнительная фильтрация
            if ($requestFilter && !$requestFilter($request)) {
                return false;
            }
            
            return true;
        });
    }

    /**
     * Создать fixture из реального HTTP запроса
     */
    public static function createFixtureFromRequest(string $fixtureName, string $url, string $method = 'GET', array $headers = [], array $body = []): void
    {
        $fixture = [
            'request' => [
                'method' => $method,
                'url' => $url,
                'headers' => $headers,
                'body' => $body
            ],
            'response' => [
                'status' => 200,
                'headers' => [],
                'body' => null
            ]
        ];
        
        $filePath = base_path(self::$fixturesPath . $fixtureName . '.json');
        $directory = dirname($filePath);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($filePath, json_encode($fixture, JSON_PRETTY_PRINT));
    }

    /**
     * Очистить кэш fixtures
     */
    public static function clearFixturesCache(): void
    {
        self::$fixtures = [];
    }

    /**
     * Получить список доступных fixtures
     */
    public static function getAvailableFixtures(): array
    {
        $fixturesPath = base_path(self::$fixturesPath);
        $fixtures = [];
        
        if (is_dir($fixturesPath)) {
            $files = glob($fixturesPath . '*.json');
            foreach ($files as $file) {
                $fixtures[] = basename($file, '.json');
            }
        }
        
        return $fixtures;
    }

    /**
     * Валидировать fixture
     */
    public static function validateFixture(string $fixtureName): bool
    {
        try {
            $fixture = self::loadFixture($fixtureName);
            
            // Проверяем обязательные поля
            $requiredFields = ['request', 'response'];
            foreach ($requiredFields as $field) {
                if (!isset($fixture[$field])) {
                    return false;
                }
            }
            
            // Проверяем структуру request
            $requestRequired = ['method', 'url'];
            foreach ($requestRequired as $field) {
                if (!isset($fixture['request'][$field])) {
                    return false;
                }
            }
            
            // Проверяем структуру response
            $responseRequired = ['status'];
            foreach ($responseRequired as $field) {
                if (!isset($fixture['response'][$field])) {
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
}



