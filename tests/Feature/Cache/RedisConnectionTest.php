<?php

namespace Tests\Feature\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RedisConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }
        
        if (config('cache.default') === 'array') {
            $this->markTestSkipped('Redis tests skipped when using array cache driver');
        }
        
        try {
            Redis::connection()->ping();
        } catch (\Exception $e) {
            $this->markTestSkipped('Redis is not available: ' . $e->getMessage());
        }
    }

    /**
     * Тест подключения к Redis
     */
    public function test_redis_connection()
    {
        try {
            $redis = Redis::connection();
            $this->assertNotNull($redis);
            
            $pong = $redis->ping();
            $this->assertEquals('PONG', $pong);
            
        } catch (\Exception $e) {
            $this->fail('Redis connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Тест базовых операций с Redis
     */
    public function test_redis_basic_operations()
    {
        try {
            $key = 'test_key_' . time();
            $value = 'test_value_' . time();
            
            $result = Redis::set($key, $value);
            $this->assertTrue($result);
            
            $retrieved = Redis::get($key);
            $this->assertEquals($value, $retrieved);
            
            $deleted = Redis::del($key);
            $this->assertEquals(1, $deleted);
            
        } catch (\Exception $e) {
            $this->fail('Redis basic operations failed: ' . $e->getMessage());
        }
    }

    /**
     * Тест кеширования через Laravel Cache
     */
    public function test_cache_operations()
    {
        try {
            $key = 'cache_test_' . time();
            $value = ['test' => 'data', 'timestamp' => time()];
            
            $result = Cache::put($key, $value, 60);
            $this->assertTrue($result);
            
            $retrieved = Cache::get($key);
            $this->assertEquals($value, $retrieved);
            
            $exists = Cache::has($key);
            $this->assertTrue($exists);
            
 из кеша
            $forgotten = Cache::forget($key);
            $this->assertTrue($forgotten);
            
            $existsAfterDelete = Cache::has($key);
            $this->assertFalse($existsAfterDelete);
            
        } catch (\Exception $e) {
            $this->fail('Cache operations failed: ' . $e->getMessage());
        }
    }

    /**
     * Тест работы с тегами кеша
     */
    public function test_cache_tags()
    {
        try {
            $key1 = 'tagged_test_1_' . time();
            $key2 = 'tagged_test_2_' . time();
            $value1 = 'value1';
            $value2 = 'value2';
            $tags = ['test', 'cache'];
            
            Cache::tags($tags)->put($key1, $value1, 60);
            Cache::tags($tags)->put($key2, $value2, 60);
            
            $this->assertEquals($value1, Cache::tags($tags)->get($key1));
            $this->assertEquals($value2, Cache::tags($tags)->get($key2));
            
            Cache::tags($tags)->flush();
            
            $this->assertNull(Cache::tags($tags)->get($key1));
            $this->assertNull(Cache::tags($tags)->get($key2));
            
        } catch (\Exception $e) {
            $this->fail('Cache tags operations failed: ' . $e->getMessage());
        }
    }

    /**
     * Тест конфигурации кеша
     */
    public function test_cache_configuration()
    {
        $defaultStore = config('cache.default');
        $this->assertNotEmpty($defaultStore);
        
        $redisConfig = config('database.redis');
        $this->assertIsArray($redisConfig);
        $this->assertArrayHasKey('default', $redisConfig);
        $this->assertArrayHasKey('cache', $redisConfig);
    }

    /**
     * Тест производительности Redis
     */
    public function test_redis_performance()
    {
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $key = 'perf_test_' . $i;
            $value = 'value_' . $i;
            
            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            
            $this->assertEquals($value, $retrieved);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(1.0, $executionTime, "Redis operations took too long: {$executionTime}s");
        
        // Очищаем тестовые данные
        for ($i = 0; $i < 100; $i++) {
            Cache::forget('perf_test_' . $i);
        }
    }
}
