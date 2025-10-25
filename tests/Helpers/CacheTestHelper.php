<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Helper для тестирования кеша
 * 
 * Обеспечивает совместимость с array драйвером в тестах
 */
class CacheTestHelper
{
    /**
     * Настроить кеш для тестов
     */
    public static function setupTestCache(): void
    {
        // Убеждаемся, что используется array драйвер
        Config::set('cache.default', 'array');
        Config::set('cache.stores.array.driver', 'array');
        
        // Очищаем кеш перед тестами
        Cache::flush();
    }

    /**
     * Проверить, что кеш работает
     */
    public static function assertCacheWorks(): void
    {
        $key = 'test_key_' . uniqid();
        $value = 'test_value';
        
        // Тест записи
        Cache::put($key, $value, 60);
        
        // Тест чтения
        $cached = Cache::get($key);
        
        if ($cached !== $value) {
            throw new \Exception('Cache is not working properly');
        }
        
        // Очистка
        Cache::forget($key);
    }

    /**
     * Получить статистику кеша
     */
    public static function getCacheStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'store' => config('cache.stores.' . config('cache.default') . '.driver'),
            'redis_available' => extension_loaded('redis'),
            'predis_available' => class_exists('Predis\Client'),
        ];
    }

    /**
     * Создать мок для Redis операций
     */
    public static function createRedisMock(): \Mockery\MockInterface
    {
        $mock = \Mockery::mock('Redis');
        
        $mock->shouldReceive('get')->andReturn(null);
        $mock->shouldReceive('set')->andReturn(true);
        $mock->shouldReceive('del')->andReturn(1);
        $mock->shouldReceive('exists')->andReturn(false);
        $mock->shouldReceive('keys')->andReturn([]);
        $mock->shouldReceive('info')->andReturn(['used_memory' => 0, 'used_memory_human' => '0B']);
        $mock->shouldReceive('incr')->andReturn(1);
        $mock->shouldReceive('setex')->andReturn(true);
        $mock->shouldReceive('expire')->andReturn(true);
        
        return $mock;
    }

    /**
     * Создать мок для CacheMetricsService
     */
    public static function createCacheMetricsMock(): \Mockery\MockInterface
    {
        $mock = \Mockery::mock(\App\Services\Cache\CacheMetricsService::class);
        
        $mock->shouldReceive('recordHit')->andReturn();
        $mock->shouldReceive('recordMiss')->andReturn();
        $mock->shouldReceive('getHitRate')->andReturn(85.5);
        $mock->shouldReceive('getMissRate')->andReturn(14.5);
        $mock->shouldReceive('getOverallStats')->andReturn([
            'hits' => 100,
            'misses' => 20,
            'hit_rate' => 83.33,
            'miss_rate' => 16.67,
            'total_operations' => 120,
            'slow_operations' => 2,
            'memory_usage' => [],
            'cache_size' => 50,
        ]);
        $mock->shouldReceive('getTagStats')->andReturn([]);
        $mock->shouldReceive('getKeyStats')->andReturn([]);
        $mock->shouldReceive('getMemoryUsage')->andReturn([
            'used_memory' => 1024000,
            'used_memory_human' => '1.00M',
            'used_memory_peak' => 2048000,
            'used_memory_peak_human' => '2.00M',
        ]);
        $mock->shouldReceive('getCacheSize')->andReturn(100);
        $mock->shouldReceive('clearMetrics')->andReturn(true);
        $mock->shouldReceive('exportMetrics')->andReturn([
            'timestamp' => now()->toISOString(),
            'overall' => [],
            'tags' => [],
            'top_keys' => [],
            'memory' => [],
        ]);
        
        return $mock;
    }
}
