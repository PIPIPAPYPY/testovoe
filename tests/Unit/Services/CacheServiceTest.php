<?php

namespace Tests\Unit\Services;

use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new CacheService();
    }

    /**
     * Тест базовых операций кеширования
     */
    public function test_basic_cache_operations()
    {
        $key = 'test_key_' . time();
        $value = 'test_value_' . time();
        
        $result = $this->cacheService->put($key, $value, 60);
        $this->assertTrue($result);
        
        $retrieved = $this->cacheService->get($key);
        $this->assertEquals($value, $retrieved);
        
        $exists = $this->cacheService->has($key);
        $this->assertTrue($exists);
        
        $deleted = $this->cacheService->forget($key);
        $this->assertTrue($deleted);
        
        $existsAfterDelete = $this->cacheService->has($key);
        $this->assertFalse($existsAfterDelete);
    }

    /**
     * Тест операций с тегами
     */
    public function test_cache_with_tags()
    {
        $key = 'tagged_test_' . time();
        $value = 'tagged_value';
        $tags = ['test', 'cache'];
        
        $result = $this->cacheService->put($key, $value, 60, $tags);
        $this->assertTrue($result);
        
        $retrieved = $this->cacheService->get($key, $tags);
        $this->assertEquals($value, $retrieved);
        
        $flushed = $this->cacheService->flushTags($tags);
        $this->assertTrue($flushed);
        
        $retrievedAfterFlush = $this->cacheService->get($key, $tags);
        $this->assertNull($retrievedAfterFlush);
    }

    /**
     * Тест метода remember
     */
    public function test_remember_method()
    {
        $key = 'remember_test_' . time();
        $expectedValue = 'computed_value_' . time();
        $callCount = 0;
        
        $callback = function () use (&$callCount, $expectedValue) {
            $callCount++;
            return $expectedValue;
        };
        
        $result1 = $this->cacheService->remember($key, $callback, 60);
        $this->assertEquals($expectedValue, $result1);
        $this->assertEquals(1, $callCount);
        
        $result2 = $this->cacheService->remember($key, $callback, 60);
        $this->assertEquals($expectedValue, $result2);
        $this->assertEquals(1, $callCount); // Callback не должен вызываться повторно
        
        // Очищаем
        $this->cacheService->forget($key);
    }

    /**
     * Тест TTL констант
     */
    public function test_ttl_constants()
    {
        $this->assertEquals(300, CacheService::TTL_ANALYTICS);
        $this->assertEquals(180, CacheService::TTL_LISTS);
        $this->assertEquals(900, CacheService::TTL_USER);
        $this->assertEquals(3600, CacheService::TTL_STATIC);
        $this->assertEquals(600, CacheService::TTL_API);
    }

    /**
     * Тест метода getTtl
     */
    public function test_get_ttl_method()
    {
        $this->assertEquals(300, $this->cacheService->getTtl('analytics'));
        $this->assertEquals(180, $this->cacheService->getTtl('lists'));
        $this->assertEquals(900, $this->cacheService->getTtl('user'));
        $this->assertEquals(3600, $this->cacheService->getTtl('static'));
        $this->assertEquals(600, $this->cacheService->getTtl('api'));
        $this->assertEquals(180, $this->cacheService->getTtl('unknown')); // default
    }

    /**
     * Тест генерации тегов
     */
    public function test_tag_generation()
    {
        $userId = 123;
        
        $userTags = $this->cacheService->getUserTags($userId);
        $this->assertContains('user:123', $userTags);
        $this->assertContains('tasks', $userTags);
        $this->assertContains('analytics', $userTags);
        
        $analyticsTags = $this->cacheService->getAnalyticsTags($userId);
        $this->assertContains('analytics', $analyticsTags);
        $this->assertContains('user:123', $analyticsTags);
        $this->assertContains('analytics:creation', $analyticsTags);
        
        $apiTags = $this->cacheService->getApiTags('tasks', $userId);
        $this->assertContains('api', $apiTags);
        $this->assertContains('api:tasks', $apiTags);
        $this->assertContains('user:123', $apiTags);
        
        $staticTags = $this->cacheService->getStaticTags();
        $this->assertContains('static', $staticTags);
        $this->assertContains('config', $staticTags);
    }

    /**
     * Тест обработки ошибок
     */
    public function test_error_handling()
    {
        $result = $this->cacheService->put('', 'value', 60);
        $this->assertFalse($result);
        
        $result = $this->cacheService->get(null);
        $this->assertNull($result);
        
        $result = $this->cacheService->get('');
        $this->assertNull($result);
    }

    /**
     * Тест очистки всего кеша
     */
    public function test_flush_all_cache()
    {
        $this->cacheService->put('test1', 'value1', 60);
        $this->cacheService->put('test2', 'value2', 60);
        
        $this->assertTrue($this->cacheService->has('test1'));
        $this->assertTrue($this->cacheService->has('test2'));
        
        $result = $this->cacheService->flush();
        $this->assertTrue($result);
        
        $this->assertFalse($this->cacheService->has('test1'));
        $this->assertFalse($this->cacheService->has('test2'));
    }
}