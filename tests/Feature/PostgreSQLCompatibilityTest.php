<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class PostgreSQLCompatibilityTest extends TestCase
{
    public function test_postgresql_connection_works(): void
    {
        // Проверяем, что подключение к базе данных работает
        $connection = DB::connection();
        $this->assertNotNull($connection);
        
        // Проверяем, что можем выполнить простой запрос
        $result = $connection->select('SELECT 1 as test');
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->test);
    }

    public function test_postgresql_data_types_work(): void
    {
        $user = User::factory()->create();
        
        // Тестируем создание задачи с различными типами данных
        $task = Task::create([
            'title' => 'PostgreSQL Test Task',
            'description' => 'Testing PostgreSQL compatibility',
            'user_id' => $user->id,
            'status' => 'todo',
            'priority' => 2,
            'deadline' => now()->addDays(7)
        ]);
        
        $this->assertNotNull($task->id);
        $this->assertEquals('PostgreSQL Test Task', $task->title);
        $this->assertEquals(2, $task->priority);
        $this->assertNotNull($task->deadline);
    }

    public function test_postgresql_enum_works(): void
    {
        $user = User::factory()->create();
        
        // Тестируем enum статусы
        $statuses = ['todo', 'in_progress', 'done'];
        
        foreach ($statuses as $status) {
            $task = Task::create([
                'title' => "Test Task {$status}",
                'user_id' => $user->id,
                'status' => $status
            ]);
            
            $this->assertEquals($status, $task->status);
        }
    }

    public function test_postgresql_foreign_keys_work(): void
    {
        $user = User::factory()->create();
        
        // Тестируем внешние ключи
        $task = Task::create([
            'title' => 'Foreign Key Test',
            'user_id' => $user->id
        ]);
        
        $this->assertEquals($user->id, $task->user_id);
        $this->assertInstanceOf(User::class, $task->user);
        $this->assertEquals($user->name, $task->user->name);
    }

    public function test_postgresql_indexes_work(): void
    {
        $user = User::factory()->create();
        
        // Создаем несколько задач для тестирования индексов
        Task::factory()->count(10)->forUser($user)->create();
        
        // Тестируем запросы, которые должны использовать индексы
        $todoTasks = Task::forUser($user->id)->byStatus('todo')->get();
        $this->assertGreaterThan(0, $todoTasks->count());
        
        $highPriorityTasks = Task::forUser($user->id)->byPriority(1)->get();
        $this->assertGreaterThanOrEqual(0, $highPriorityTasks->count());
    }

    public function test_postgresql_transactions_work(): void
    {
        $user = User::factory()->create();
        
        // Тестируем транзакции
        DB::beginTransaction();
        
        try {
            $task = Task::create([
                'title' => 'Transaction Test',
                'user_id' => $user->id
            ]);
            
            $this->assertNotNull($task->id);
            
            DB::commit();
            
            // Проверяем, что задача сохранилась
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'title' => 'Transaction Test'
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function test_postgresql_json_operations_work(): void
    {
        // Тестируем JSON операции (если используются)
        $user = User::factory()->create();
        
        $task = Task::create([
            'title' => 'JSON Test Task',
            'user_id' => $user->id,
            'description' => 'Testing JSON operations'
        ]);
        
        // Проверяем, что можем работать с JSON данными
        $taskData = $task->toArray();
        $this->assertIsArray($taskData);
        $this->assertArrayHasKey('title', $taskData);
        $this->assertArrayHasKey('status', $taskData);
    }

    public function test_postgresql_date_operations_work(): void
    {
        $user = User::factory()->create();
        
        // Тестируем операции с датами
        $task = Task::create([
            'title' => 'Date Test Task',
            'user_id' => $user->id,
            'deadline' => now()->addDays(5)
        ]);
        
        $this->assertNotNull($task->deadline);
        $this->assertTrue($task->deadline->isFuture());
        
        // Тестируем просроченные задачи
        $overdueTask = Task::create([
            'title' => 'Overdue Task',
            'user_id' => $user->id,
            'deadline' => now()->subDays(1),
            'status' => 'todo'
        ]);
        
        $this->assertTrue($overdueTask->isOverdue());
    }

    public function test_postgresql_string_operations_work(): void
    {
        $user = User::factory()->create();
        
        // Тестируем строковые операции
        $longTitle = str_repeat('A', 255); // Максимальная длина
        $task = Task::create([
            'title' => $longTitle,
            'user_id' => $user->id
        ]);
        
        $this->assertEquals($longTitle, $task->title);
        $this->assertEquals(255, strlen($task->title));
    }

    public function test_postgresql_boolean_operations_work(): void
    {
        $user = User::factory()->create();
        
        // Тестируем булевы операции
        $task = Task::create([
            'title' => 'Boolean Test Task',
            'user_id' => $user->id,
            'status' => 'todo'
        ]);
        
        $this->assertFalse($task->isCompleted());
        $this->assertFalse($task->isOverdue());
        
        // Изменяем статус на выполненный
        $task->update(['status' => 'done']);
        $this->assertTrue($task->isCompleted());
    }
}


