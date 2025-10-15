<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Увеличиваем лимиты для тестов производительности
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);
    }

    public function test_bulk_task_creation_performance(): void
    {
        $user = User::factory()->create();
        
        $startTime = microtime(true);
        
        // Создаем 1000 задач пакетно
        $tasks = [];
        for ($i = 0; $i < 1000; $i++) {
            $tasks[] = [
                'title' => "Performance Test Task {$i}",
                'description' => "Testing bulk creation performance",
                'user_id' => $user->id,
                'status' => 'todo',
                'priority' => rand(1, 3),
                'deadline' => now()->addDays(rand(1, 30)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        Task::insert($tasks);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Проверяем, что создание заняло менее 5 секунд
        $this->assertLessThan(5.0, $executionTime, 'Bulk task creation took too long');
        
        // Проверяем, что все задачи созданы
        $this->assertDatabaseCount('tasks', 1000);
    }

    public function test_complex_query_performance(): void
    {
        $user = User::factory()->create();
        
        // Создаем тестовые данные
        Task::factory()->count(500)->forUser($user)->create([
            'status' => 'todo',
            'priority' => 1
        ]);
        
        Task::factory()->count(300)->forUser($user)->create([
            'status' => 'in_progress',
            'priority' => 2
        ]);
        
        Task::factory()->count(200)->forUser($user)->create([
            'status' => 'done',
            'priority' => 3
        ]);
        
        $startTime = microtime(true);
        
        // Сложный запрос с джойнами и фильтрами
        $results = DB::table('tasks')
            ->join('users', 'tasks.user_id', '=', 'users.id')
            ->where('tasks.user_id', $user->id)
            ->whereIn('tasks.status', ['todo', 'in_progress'])
            ->where('tasks.priority', '>=', 2)
            ->where('tasks.deadline', '>', now())
            ->select('tasks.*', 'users.name as user_name')
            ->orderBy('tasks.priority', 'asc')
            ->orderBy('tasks.deadline', 'asc')
            ->get();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Проверяем, что запрос выполнился менее чем за 1 секунду
        $this->assertLessThan(1.0, $executionTime, 'Complex query took too long');
        
        // Проверяем результаты
        $this->assertGreaterThan(0, $results->count());
    }

    public function test_task_service_performance(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи для тестирования сервиса
        Task::factory()->count(100)->forUser($user)->create();
        
        // Создаем мок для AnalyticsServiceInterface
        $analyticsService = $this->createMock(\App\Services\Analytics\AnalyticsServiceInterface::class);
        $taskService = new \App\Services\TaskService($analyticsService);
        
        $startTime = microtime(true);
        
        // Тестируем различные операции сервиса
        for ($i = 0; $i < 50; $i++) {
            $taskService->getUserTasks($user->id);
            $taskService->getStatusCounts($user->id);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Проверяем, что операции сервиса выполняются быстро
        $this->assertLessThan(2.0, $executionTime, 'Task service operations took too long');
    }

    public function test_database_transaction_performance(): void
    {
        $user = User::factory()->create();
        
        $startTime = microtime(true);
        
        // Тестируем производительность транзакций
        for ($i = 0; $i < 100; $i++) {
            DB::transaction(function () use ($user, $i) {
                Task::create([
                    'title' => "Transaction Test Task {$i}",
                    'user_id' => $user->id,
                    'status' => 'todo'
                ]);
            });
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Проверяем, что транзакции выполняются быстро
        $this->assertLessThan(3.0, $executionTime, 'Database transactions took too long');
        
        // Проверяем, что все задачи созданы
        $this->assertDatabaseCount('tasks', 100);
    }

    public function test_memory_usage_during_large_operations(): void
    {
        $user = User::factory()->create();
        
        $initialMemory = memory_get_usage(true);
        
        // Создаем много данных
        $tasks = [];
        for ($i = 0; $i < 5000; $i++) {
            $tasks[] = [
                'title' => "Memory Test Task {$i}",
                'user_id' => $user->id,
                'status' => 'todo',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        Task::insert($tasks);
        
        $peakMemory = memory_get_peak_usage(true);
        $memoryUsed = $peakMemory - $initialMemory;
        
        // Проверяем, что использование памяти разумное (менее 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage too high during large operations');
    }

    public function test_concurrent_operations_performance(): void
    {
        $user = User::factory()->create();
        
        $startTime = microtime(true);
        
        // Симулируем конкурентные операции
        $operations = [];
        for ($i = 0; $i < 50; $i++) {
            $operations[] = function () use ($user, $i) {
                return Task::create([
                    'title' => "Concurrent Test Task {$i}",
                    'user_id' => $user->id,
                    'status' => 'todo'
                ]);
            };
        }
        
        // Выполняем операции последовательно (в реальности они были бы параллельными)
        foreach ($operations as $operation) {
            $operation();
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Проверяем производительность конкурентных операций
        $this->assertLessThan(2.0, $executionTime, 'Concurrent operations took too long');
        
        // Проверяем, что все задачи созданы
        $this->assertDatabaseCount('tasks', 50);
    }

    public function test_index_usage_performance(): void
    {
        $user = User::factory()->create();
        
        // Создаем данные для тестирования индексов
        Task::factory()->count(1000)->forUser($user)->create();
        
        $startTime = microtime(true);
        
        // Запросы, которые должны использовать индексы
        $todoTasks = Task::where('user_id', $user->id)
            ->where('status', 'todo')
            ->get();
            
        $highPriorityTasks = Task::where('user_id', $user->id)
            ->where('priority', 1)
            ->get();
            
        $overdueTasks = Task::where('user_id', $user->id)
            ->where('deadline', '<', now())
            ->where('status', '!=', 'done')
            ->get();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Проверяем, что индексированные запросы выполняются быстро
        $this->assertLessThan(0.5, $executionTime, 'Indexed queries took too long');
        
        // Проверяем результаты
        $this->assertGreaterThan(0, $todoTasks->count());
    }
}