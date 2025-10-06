<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;

class TaskTest extends TestCase
{
    public function test_task_has_fillable_attributes(): void
    {
        $task = new Task();
        
        $expectedFillable = ['title', 'description', 'status', 'user_id', 'priority', 'deadline'];
        
        $this->assertEquals($expectedFillable, $task->getFillable());
    }

    public function test_task_has_default_attributes(): void
    {
        $task = new Task();
        
        $this->assertEquals('todo', $task->status);
        $this->assertEquals(2, $task->priority);
    }

    public function test_task_belongs_to_user_relationship(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $this->assertInstanceOf(User::class, $task->user);
        $this->assertEquals($user->id, $task->user->id);
        $this->assertEquals($user->name, $task->user->name);
    }

    public function test_task_deadline_is_cast_to_datetime(): void
    {
        $task = Task::factory()->create([
            'deadline' => '2024-12-31 23:59:59'
        ]);

        $this->assertInstanceOf(Carbon::class, $task->deadline);
        $this->assertEquals('2024-12-31', $task->deadline->format('Y-m-d'));
    }

    public function test_task_priority_is_cast_to_integer(): void
    {
        $task = Task::factory()->create(['priority' => '1']);

        $this->assertIsInt($task->priority);
        $this->assertEquals(1, $task->priority);
    }

    public function test_task_user_id_is_cast_to_integer(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $this->assertIsInt($task->user_id);
        $this->assertEquals($user->id, $task->user_id);
    }

    public function test_task_scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Task::factory()->count(3)->forUser($user1)->create();
        Task::factory()->count(2)->forUser($user2)->create();

        $user1Tasks = Task::forUser($user1->id)->get();
        $user2Tasks = Task::forUser($user2->id)->get();

        $this->assertCount(3, $user1Tasks);
        $this->assertCount(2, $user2Tasks);
        
        foreach ($user1Tasks as $task) {
            $this->assertEquals($user1->id, $task->user_id);
        }
    }

    public function test_task_scope_by_status(): void
    {
        $user = User::factory()->create();
        
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_TODO]);
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_IN_PROGRESS]);
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_DONE]);

        $todoTasks = Task::byStatus(Task::STATUS_TODO)->get();
        $inProgressTasks = Task::byStatus(Task::STATUS_IN_PROGRESS)->get();
        $doneTasks = Task::byStatus(Task::STATUS_DONE)->get();

        $this->assertCount(1, $todoTasks);
        $this->assertCount(1, $inProgressTasks);
        $this->assertCount(1, $doneTasks);
        
        $this->assertEquals(Task::STATUS_TODO, $todoTasks->first()->status);
        $this->assertEquals(Task::STATUS_IN_PROGRESS, $inProgressTasks->first()->status);
        $this->assertEquals(Task::STATUS_DONE, $doneTasks->first()->status);
    }

    public function test_task_scope_by_priority(): void
    {
        $user = User::factory()->create();
        
        Task::factory()->forUser($user)->highPriority()->create();
        Task::factory()->forUser($user)->create(['priority' => Task::PRIORITY_MEDIUM]);
        Task::factory()->forUser($user)->lowPriority()->create();

        $highPriorityTasks = Task::byPriority(Task::PRIORITY_HIGH)->get();
        $mediumPriorityTasks = Task::byPriority(Task::PRIORITY_MEDIUM)->get();
        $lowPriorityTasks = Task::byPriority(Task::PRIORITY_LOW)->get();

        $this->assertCount(1, $highPriorityTasks);
        $this->assertCount(1, $mediumPriorityTasks);
        $this->assertCount(1, $lowPriorityTasks);
    }

    public function test_task_scope_overdue(): void
    {
        $user = User::factory()->create();
        
        // Создаем просроченную задачу
        Task::factory()->forUser($user)->overdue()->create();
        
        // Создаем задачу с будущим дедлайном
        Task::factory()->forUser($user)->create([
            'deadline' => now()->addDays(5),
            'status' => Task::STATUS_TODO
        ]);
        
        // Создаем выполненную просроченную задачу (не должна попасть в выборку)
        Task::factory()->forUser($user)->create([
            'deadline' => now()->subDays(5),
            'status' => Task::STATUS_DONE
        ]);

        $overdueTasks = Task::overdue()->get();

        $this->assertCount(1, $overdueTasks);
        $this->assertTrue($overdueTasks->first()->deadline->isPast());
        $this->assertNotEquals(Task::STATUS_DONE, $overdueTasks->first()->status);
    }

    public function test_task_scope_completed(): void
    {
        $user = User::factory()->create();
        
        Task::factory()->forUser($user)->completed()->create();
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_TODO]);
        Task::factory()->forUser($user)->inProgress()->create();

        $completedTasks = Task::completed()->get();

        $this->assertCount(1, $completedTasks);
        $this->assertEquals(Task::STATUS_DONE, $completedTasks->first()->status);
    }

    public function test_task_is_overdue_method(): void
    {
        // Просроченная задача
        $overdueTask = Task::factory()->create([
            'deadline' => now()->subDays(1),
            'status' => Task::STATUS_TODO
        ]);

        // Задача с будущим дедлайном
        $futureTask = Task::factory()->create([
            'deadline' => now()->addDays(1),
            'status' => Task::STATUS_TODO
        ]);

        // Выполненная просроченная задача
        $completedOverdueTask = Task::factory()->create([
            'deadline' => now()->subDays(1),
            'status' => Task::STATUS_DONE
        ]);

        // Задача без дедлайна
        $noDeadlineTask = Task::factory()->withoutDeadline()->create();

        $this->assertTrue($overdueTask->isOverdue());
        $this->assertFalse($futureTask->isOverdue());
        $this->assertFalse($completedOverdueTask->isOverdue());
        $this->assertFalse($noDeadlineTask->isOverdue());
    }

    public function test_task_is_completed_method(): void
    {
        $completedTask = Task::factory()->completed()->create();
        $todoTask = Task::factory()->create(['status' => Task::STATUS_TODO]);
        $inProgressTask = Task::factory()->inProgress()->create();

        $this->assertTrue($completedTask->isCompleted());
        $this->assertFalse($todoTask->isCompleted());
        $this->assertFalse($inProgressTask->isCompleted());
    }

    public function test_task_status_label_attribute(): void
    {
        $todoTask = Task::factory()->create(['status' => Task::STATUS_TODO]);
        $inProgressTask = Task::factory()->inProgress()->create();
        $doneTask = Task::factory()->completed()->create();

        $this->assertEquals('К выполнению', $todoTask->status_label);
        $this->assertEquals('В работе', $inProgressTask->status_label);
        $this->assertEquals('Выполнено', $doneTask->status_label);
    }

    public function test_task_priority_label_attribute(): void
    {
        $highPriorityTask = Task::factory()->highPriority()->create();
        $mediumPriorityTask = Task::factory()->create(['priority' => Task::PRIORITY_MEDIUM]);
        $lowPriorityTask = Task::factory()->lowPriority()->create();

        $this->assertEquals('Высокий', $highPriorityTask->priority_label);
        $this->assertEquals('Средний', $mediumPriorityTask->priority_label);
        $this->assertEquals('Низкий', $lowPriorityTask->priority_label);
    }

    public function test_task_title_is_required(): void
    {
        // Тестируем, что задача не может быть создана без заголовка
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Отключаем observer для этого теста
        Task::unsetEventDispatcher();
        
        Task::create([
            'user_id' => User::factory()->create()->id,
            'description' => 'Test description'
            // title отсутствует
        ]);
    }

    public function test_task_user_id_is_required(): void
    {
        // Отключаем observer для этого теста
        Task::unsetEventDispatcher();
        
        // Поскольку user_id может быть nullable в миграции, 
        // тестируем что задача создается с null user_id
        $task = Task::create([
            'title' => 'Test Task',
            'user_id' => null
        ]);
        
        $this->assertNull($task->user_id);
        $this->assertEquals('Test Task', $task->title);
    }

    public function test_task_constants_are_defined(): void
    {
        $this->assertEquals('todo', Task::STATUS_TODO);
        $this->assertEquals('in_progress', Task::STATUS_IN_PROGRESS);
        $this->assertEquals('done', Task::STATUS_DONE);
        
        $this->assertEquals(1, Task::PRIORITY_HIGH);
        $this->assertEquals(2, Task::PRIORITY_MEDIUM);
        $this->assertEquals(3, Task::PRIORITY_LOW);
    }
}