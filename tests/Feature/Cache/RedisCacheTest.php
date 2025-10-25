<?php

namespace Tests\Feature\Cache;

use App\Models\Task;
use App\Models\User;
use App\Services\Cache\CacheService;
use App\Services\Cache\CacheKeyGenerator;
use App\Services\Cache\UserCacheService;
use App\Services\Cache\StaticCacheService;
use App\Services\Analytics\TaskAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Тесты Redis кеширования
 * 
 * Проверяет работу tag-based инвалидации, кеширование данных
 * и производительность кеша
 */
class RedisCacheTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CacheService $cacheService;
    private CacheKeyGenerator $keyGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Настраиваем кеш для тестов
        \Tests\Helpers\CacheTestHelper::setupTestCache();
        
        $this->user = User::factory()->create();
        $this->cacheService = app(CacheService::class);
        $this->keyGenerator = app(CacheKeyGenerator::class);
    }

    /**
     * Тест tag-based инвалидации кеша
     */
    public function test_tag_based_cache_invalidation(): void
    {
        // Создаем задачи
        $task1 = Task::factory()->create(['user_id' => $this->user->id]);
        $task2 = Task::factory()->create(['user_id' => $this->user->id]);

        // Кешируем данные пользователя
        $userCacheService = app(UserCacheService::class);
        $userProfile = $userCacheService->getUserProfile($this->user->id);
        
        $this->assertNotNull($userProfile);
        $this->assertTrue($userCacheService->isUserCached($this->user->id));

        // Кешируем аналитику
        $analyticsService = app(TaskAnalyticsService::class);
        $overallStats = $analyticsService->getOverallStats($this->user->id);
        
        $this->assertIsArray($overallStats);
        $this->assertArrayHasKey('total_tasks', $overallStats);

        // Создаем новую задачу (должно очистить кеш)
        $task3 = Task::factory()->create(['user_id' => $this->user->id]);

        // Проверяем, что кеш очищен
        $this->assertFalse($userCacheService->isUserCached($this->user->id));
    }

    /**
     * Тест кеширования списков задач
     */
    public function test_task_list_caching(): void
    {
        // Создаем задачи
        Task::factory()->count(5)->create(['user_id' => $this->user->id]);

        $taskService = app(\App\Services\TaskService::class);
        
        // Первый запрос - должен загрузить из БД
        $startTime = microtime(true);
        $tasks1 = $taskService->getUserTasks($this->user->id);
        $firstDuration = microtime(true) - $startTime;

        // Второй запрос - должен загрузить из кеша
        $startTime = microtime(true);
        $tasks2 = $taskService->getUserTasks($this->user->id);
        $secondDuration = microtime(true) - $startTime;

        $this->assertEquals($tasks1->count(), $tasks2->count());
        $this->assertLessThan($firstDuration, $secondDuration);
    }

    /**
     * Тест кеширования с фильтрами
     */
    public function test_filtered_task_caching(): void
    {
        // Создаем задачи с разными статусами
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'todo'
        ]);
        
        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'done'
        ]);

        $taskService = app(\App\Services\TaskService::class);
        
        // Кешируем отфильтрованные списки
        $todoTasks = $taskService->getUserTasks($this->user->id, ['status' => 'todo']);
        $doneTasks = $taskService->getUserTasks($this->user->id, ['status' => 'done']);
        
        $this->assertEquals(3, $todoTasks->count());
        $this->assertEquals(2, $doneTasks->count());

        // Проверяем, что кеш работает
        $todoTasksCached = $taskService->getUserTasks($this->user->id, ['status' => 'todo']);
        $this->assertEquals(3, $todoTasksCached->count());
    }

    /**
     * Тест кеширования аналитики
     */
    public function test_analytics_caching(): void
    {
        // Создаем задачи для аналитики
        Task::factory()->count(10)->create(['user_id' => $this->user->id]);
        
        Task::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'status' => 'done'
        ]);

        $analyticsService = app(TaskAnalyticsService::class);
        
        // Первый запрос
        $startTime = microtime(true);
        $stats1 = $analyticsService->getOverallStats($this->user->id);
        $firstDuration = microtime(true) - $startTime;

        // Второй запрос (должен быть из кеша)
        $startTime = microtime(true);
        $stats2 = $analyticsService->getOverallStats($this->user->id);
        $secondDuration = microtime(true) - $startTime;

        $this->assertEquals($stats1, $stats2);
        $this->assertLessThan($firstDuration, $secondDuration);
    }

    /**
     * Тест кеширования статических данных
     */
    public function test_static_data_caching(): void
    {
        $staticCacheService = app(StaticCacheService::class);
        
        // Получаем статические данные
        $statuses = $staticCacheService->getStatuses();
        $priorities = $staticCacheService->getPriorities();
        
        $this->assertIsArray($statuses);
        $this->assertIsArray($priorities);
        $this->assertArrayHasKey('todo', $statuses);
        $this->assertArrayHasKey(1, $priorities);

        // Проверяем, что данные кешированы
        $this->assertTrue($staticCacheService->isStaticDataCached());
    }

    /**
     * Тест concurrent access с блокировками
     */
    public function test_concurrent_cache_access(): void
    {
        $cacheKey = 'test_concurrent_key';
        $tags = ['test'];
        
        // Симулируем concurrent access
        $results = [];
        $promises = [];
        
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->cacheService->remember(
                $cacheKey . '_' . $i,
                function () use ($i) {
                    usleep(10000); // 10ms задержка
                    return "result_{$i}";
                },
                60,
                $tags
            );
        }
        
        $this->assertCount(5, $results);
        $this->assertEquals('result_0', $results[0]);
    }

    /**
     * Тест очистки кеша по тегам
     */
    public function test_cache_flush_by_tags(): void
    {
        // Пропускаем тест для array драйвера, так как он не поддерживает теги
        if (config('cache.default') === 'array') {
            $this->markTestSkipped('Array cache driver does not support tag-based operations');
        }
        
        // Кешируем данные с разными тегами
        $this->cacheService->put('key1', 'value1', 60, ['user:1', 'tasks']);
        $this->cacheService->put('key2', 'value2', 60, ['user:2', 'tasks']);
        $this->cacheService->put('key3', 'value3', 60, ['user:1', 'analytics']);
        
        // Проверяем, что данные кешированы
        $this->assertEquals('value1', $this->cacheService->get('key1', ['user:1', 'tasks']));
        $this->assertEquals('value2', $this->cacheService->get('key2', ['user:2', 'tasks']));
        $this->assertEquals('value3', $this->cacheService->get('key3', ['user:1', 'analytics']));
        
        // Очищаем кеш по тегу user:1
        $this->cacheService->flushTags(['user:1']);
        
        // Проверяем, что данные user:1 очищены, а user:2 остались
        $this->assertNull($this->cacheService->get('key1', ['user:1', 'tasks']));
        $this->assertNull($this->cacheService->get('key3', ['user:1', 'analytics']));
        $this->assertEquals('value2', $this->cacheService->get('key2', ['user:2', 'tasks']));
    }

    /**
     * Тест генерации ключей кеша
     */
    public function test_cache_key_generation(): void
    {
        $userId = 1;
        $filters = ['status' => 'todo', 'priority' => 1];
        
        // Тест генерации ключей для задач
        $taskKey = $this->keyGenerator->userTasks($userId, $filters);
        $this->assertStringStartsWith('v1:', $taskKey);
        $this->assertStringContainsString('tasks:user:1', $taskKey);
        
        // Тест генерации ключей для аналитики
        $analyticsKey = $this->keyGenerator->analytics($userId, 'creation', 'month');
        $this->assertStringStartsWith('v1:', $analyticsKey);
        $this->assertStringContainsString('analytics:user:1', $analyticsKey);
        
        // Тест генерации ключей для API
        $apiKey = $this->keyGenerator->apiResponse('tasks', ['page' => 1], $userId);
        $this->assertStringStartsWith('v1:', $apiKey);
        $this->assertStringContainsString('api:response:tasks', $apiKey);
    }

    /**
     * Тест производительности кеша
     */
    public function test_cache_performance(): void
    {
        $iterations = 100;
        $cacheKey = 'performance_test';
        $tags = ['test'];
        
        // Тест записи
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->cacheService->put($cacheKey . '_' . $i, "value_{$i}", 60, $tags);
        }
        $writeTime = microtime(true) - $startTime;
        
        // Тест чтения
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $this->cacheService->get($cacheKey . '_' . $i, $tags);
        }
        $readTime = microtime(true) - $startTime;
        
        // Проверяем, что операции выполняются быстро
        $this->assertLessThan(1.0, $writeTime); // Менее 1 секунды на запись
        $this->assertLessThan(0.5, $readTime);  // Менее 0.5 секунды на чтение
    }

    /**
     * Тест очистки кеша при изменении задач
     */
    public function test_cache_clearing_on_task_changes(): void
    {
        // Создаем задачу
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        
        // Кешируем данные пользователя
        $userCacheService = app(UserCacheService::class);
        $userCacheService->warmUserCache($this->user->id);
        
        $this->assertTrue($userCacheService->isUserCached($this->user->id));
        
        // Обновляем задачу
        $task->update(['title' => 'Updated Task']);
        
        // Проверяем, что кеш очищен
        $this->assertFalse($userCacheService->isUserCached($this->user->id));
    }

    /**
     * Тест TTL кеша
     */
    public function test_cache_ttl(): void
    {
        $cacheKey = 'ttl_test';
        $tags = ['test'];
        $ttl = 1; // 1 секунда
        
        // Записываем данные с TTL
        $this->cacheService->put($cacheKey, 'test_value', $ttl, $tags);
        
        // Проверяем, что данные есть
        $this->assertEquals('test_value', $this->cacheService->get($cacheKey, $tags));
        
        // Ждем истечения TTL
        sleep(2);
        
        // Проверяем, что данные истекли
        $this->assertNull($this->cacheService->get($cacheKey, $tags));
    }

    protected function tearDown(): void
    {
        // Очищаем кеш после каждого теста
        Cache::flush();
        parent::tearDown();
    }
}
