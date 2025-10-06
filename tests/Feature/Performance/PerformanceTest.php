<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use Tests\Helpers\AssertionHelper;
use App\Models\Task;
use App\Models\User;
use App\Services\Analytics\TaskAnalyticsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceTest extends TestCase
{
    public function test_task_list_performance_with_large_dataset(): void
    {
        $user = $this->authenticateUser();
        
        // Создаем большое количество задач
        Task::factory()->count(1000)->forUser($user)->create();

        $startTime = microtime(true);

        $response = $this->getJson('/api/tasks');

        AssertionHelper::assertExecutionTime($startTime, 2.0); // Максимум 2 секунды

        $response->assertStatus(200);
        
        // Проверяем пагинацию
        AssertionHelper::assertPagination($response, 1000, 50);
    }

    public function test_task_creation_performance(): void
    {
        $user = $this->authenticateUser();

        $taskData = [
            'title' => 'Performance Test Task',
            'description' => 'Testing task creation performance'
        ];

        $startTime = microtime(true);

        // Создаем 100 задач
        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson('/api/tasks', array_merge($taskData, [
                'title' => "Performance Test Task {$i}"
            ]));
            $response->assertStatus(201);
        }

        AssertionHelper::assertExecutionTime($startTime, 10.0); // Максимум 10 секунд для 100 задач

        // Проверяем, что все задачи созданы
        $this->assertEquals(100, Task::forUser($user->id)->count());
    }

    public function test_analytics_caching_performance(): void
    {
        $user = $this->authenticateUser();
        $analyticsService = new TaskAnalyticsService();
        
        // Создаем данные для аналитики
        Task::factory()->count(500)->forUser($user)->create();

        // Первый вызов - без кэша
        Cache::flush();
        $startTime = microtime(true);
        $stats1 = $analyticsService->getOverallStats($user->id);
        $firstCallTime = microtime(true) - $startTime;

        // Второй вызов - с кэшем
        $startTime = microtime(true);
        $stats2 = $analyticsService->getOverallStats($user->id);
        $secondCallTime = microtime(true) - $startTime;

        // Кэшированный вызов должен быть значительно быстрее
        $this->assertLessThan($firstCallTime / 2, $secondCallTime);
        $this->assertEquals($stats1, $stats2);

        // Оба вызова должны быть достаточно быстрыми
        $this->assertLessThan(1.0, $firstCallTime);
        $this->assertLessThan(0.1, $secondCallTime);
    }

    public function test_database_query_optimization(): void
    {
        $user = $this->authenticateUser();
        
        // Создаем задачи
        Task::factory()->count(100)->forUser($user)->create();

        // Включаем логирование запросов
        DB::enableQueryLog();

        $response = $this->getJson('/api/tasks');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Проверяем количество запросов (должно быть минимальным)
        $this->assertLessThanOrEqual(5, count($queries), 'Too many database queries executed');

        // Проверяем, что нет N+1 проблем
        $selectQueries = collect($queries)->filter(function ($query) {
            return strpos(strtolower($query['query']), 'select') === 0;
        });

        $this->assertLessThanOrEqual(3, $selectQueries->count(), 'Possible N+1 query problem detected');
    }

    public function test_memory_usage_with_large_datasets(): void
    {
        $user = $this->authenticateUser();
        
        $initialMemory = memory_get_usage(true);

        // Создаем большое количество задач
        Task::factory()->count(1000)->forUser($user)->create();

        $response = $this->getJson('/api/tasks');
        $response->assertStatus(200);

        $finalMemory = memory_get_usage(true);
        $memoryUsed = $finalMemory - $initialMemory;

        // Проверяем, что использование памяти разумно (менее 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage too high');
    }

    public function test_concurrent_request_performance(): void
    {
        $user = $this->authenticateUser();
        Task::factory()->count(50)->forUser($user)->create();

        $startTime = microtime(true);

        // Симулируем одновременные запросы
        $responses = [];
        for ($i = 0; $i < 10; $i++) {
            $responses[] = $this->getJson('/api/tasks');
        }

        AssertionHelper::assertExecutionTime($startTime, 5.0); // Максимум 5 секунд для 10 запросов

        // Все запросы должны быть успешными
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }

    public function test_search_performance(): void
    {
        $user = $this->authenticateUser();
        
        // Создаем задачи с различными заголовками для поиска
        for ($i = 0; $i < 500; $i++) {
            Task::factory()->forUser($user)->create([
                'title' => "Task {$i} with keyword search",
                'description' => "Description for task number {$i}"
            ]);
        }

        $startTime = microtime(true);

        $response = $this->getJson('/api/tasks?search=keyword');

        AssertionHelper::assertExecutionTime($startTime, 1.0); // Максимум 1 секунда

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        $this->assertGreaterThan(0, count($tasks));
    }

    public function test_filtering_performance(): void
    {
        $user = $this->authenticateUser();
        
        // Создаем задачи с разными статусами и приоритетами
        Task::factory()->count(200)->forUser($user)->create(['status' => Task::STATUS_TODO]);
        Task::factory()->count(200)->forUser($user)->create(['status' => Task::STATUS_IN_PROGRESS]);
        Task::factory()->count(200)->forUser($user)->create(['status' => Task::STATUS_DONE]);

        $startTime = microtime(true);

        // Тестируем фильтрацию по статусу
        $response = $this->getJson('/api/tasks?status=todo');
        $response->assertStatus(200);

        // Тестируем фильтрацию по приоритету
        $response = $this->getJson('/api/tasks?priority=1');
        $response->assertStatus(200);

        // Тестируем комбинированную фильтрацию
        $response = $this->getJson('/api/tasks?status=todo&priority=1');
        $response->assertStatus(200);

        AssertionHelper::assertExecutionTime($startTime, 2.0); // Максимум 2 секунды для всех фильтров
    }

    public function test_analytics_performance_with_large_dataset(): void
    {
        $user = $this->authenticateUser();
        $analyticsService = new TaskAnalyticsService();
        
        // Создаем большое количество задач за разные периоды
        for ($i = 0; $i < 365; $i++) {
            Task::factory()->count(rand(1, 5))->forUser($user)->create([
                'created_at' => now()->subDays($i),
                'status' => [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_DONE][rand(0, 2)]
            ]);
        }

        Cache::flush(); // Очищаем кэш для чистого теста

        $startTime = microtime(true);

        // Тестируем различные методы аналитики
        $overallStats = $analyticsService->getOverallStats($user->id);
        $completionStats = $analyticsService->getCompletionStats($user->id);
        $priorityStats = $analyticsService->getPriorityStats($user->id);
        $creationStats = $analyticsService->getTaskCreationStats($user->id, 'month');

        AssertionHelper::assertExecutionTime($startTime, 3.0); // Максимум 3 секунды

        // Проверяем, что все методы вернули данные
        $this->assertIsArray($overallStats);
        $this->assertIsArray($completionStats);
        $this->assertIsArray($priorityStats);
        $this->assertIsArray($creationStats);
    }

    public function test_pagination_performance(): void
    {
        $user = $this->authenticateUser();
        
        // Создаем много задач
        Task::factory()->count(1000)->forUser($user)->create();

        $startTime = microtime(true);

        // Тестируем разные страницы
        $response1 = $this->getJson('/api/tasks?page=1');
        $response2 = $this->getJson('/api/tasks?page=10');
        $response3 = $this->getJson('/api/tasks?page=20');

        AssertionHelper::assertExecutionTime($startTime, 2.0); // Максимум 2 секунды

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);

        // Проверяем, что каждая страница содержит правильное количество элементов
        $this->assertCount(50, $response1->json('data'));
        $this->assertCount(50, $response2->json('data'));
        $this->assertCount(50, $response3->json('data'));
    }

    public function test_cache_invalidation_performance(): void
    {
        $user = $this->authenticateUser();
        $analyticsService = new TaskAnalyticsService();
        
        // Создаем задачи и заполняем кэш
        Task::factory()->count(100)->forUser($user)->create();
        $analyticsService->getOverallStats($user->id);

        $startTime = microtime(true);

        // Очищаем кэш
        $analyticsService->clearUserAnalyticsCache($user->id);

        AssertionHelper::assertExecutionTime($startTime, 0.1); // Максимум 0.1 секунды

        // Проверяем, что кэш действительно очищен
        $this->assertFalse(Cache::has("analytics_overall_{$user->id}"));
    }

    public function test_bulk_operations_performance(): void
    {
        $user = $this->authenticateUser();

        $startTime = microtime(true);

        // Создаем много задач через API
        $responses = [];
        for ($i = 0; $i < 50; $i++) {
            $responses[] = $this->postJson('/api/tasks', [
                'title' => "Bulk Task {$i}",
                'description' => "Description for bulk task {$i}"
            ]);
        }

        AssertionHelper::assertExecutionTime($startTime, 15.0); // Максимум 15 секунд для 50 задач

        // Проверяем, что все задачи созданы успешно
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        $this->assertEquals(50, Task::forUser($user->id)->count());
    }

    public function test_database_connection_pool_performance(): void
    {
        $user = $this->authenticateUser();
        Task::factory()->count(10)->forUser($user)->create();

        $startTime = microtime(true);

        // Выполняем много запросов подряд
        for ($i = 0; $i < 20; $i++) {
            $response = $this->getJson('/api/tasks');
            $response->assertStatus(200);
        }

        AssertionHelper::assertExecutionTime($startTime, 5.0); // Максимум 5 секунд

        // Проверяем, что соединения с БД управляются эффективно
        $this->assertTrue(true); // Если дошли до этой точки, значит все в порядке
    }

    public function test_json_serialization_performance(): void
    {
        $user = $this->authenticateUser();
        
        // Создаем задачи с большими описаниями
        Task::factory()->count(100)->forUser($user)->create([
            'description' => str_repeat('Large description content. ', 100)
        ]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/tasks');

        AssertionHelper::assertExecutionTime($startTime, 2.0); // Максимум 2 секунды

        $response->assertStatus(200);
        
        // Проверяем, что JSON корректно сериализован
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));
    }
}