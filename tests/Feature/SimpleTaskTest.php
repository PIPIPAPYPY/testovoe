<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_can_be_created(): void
    {
        $user = User::factory()->create();
        
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'user_id' => $user->id,
            'status' => 'todo',
            'priority' => 2
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $user->id
        ]);

        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals($user->id, $task->user_id);
    }

    public function test_task_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $task->user_id);
        $this->assertInstanceOf(User::class, $task->user);
        $this->assertEquals($user->name, $task->user->name);
    }

    public function test_user_has_many_tasks(): void
    {
        $user = User::factory()->create();
        
        Task::factory()->forUser($user)->count(3)->create();

        $this->assertEquals(3, $user->tasks()->count());
        $this->assertEquals(3, Task::where('user_id', $user->id)->count());
    }

    public function test_task_has_default_values(): void
    {
        $user = User::factory()->create();
        
        $task = Task::create([
            'title' => 'Test Task',
            'user_id' => $user->id
        ]);

        $this->assertEquals('todo', $task->status);
        $this->assertEquals(2, $task->priority);
    }

    public function test_task_scopes_work(): void
    {
        $user = User::factory()->create();
        
        Task::factory()->forUser($user)->create(['status' => 'todo']);
        Task::factory()->forUser($user)->create(['status' => 'done']);
        Task::factory()->forUser($user)->create(['status' => 'in_progress']);

        $todoTasks = Task::forUser($user->id)->byStatus('todo')->get();
        $doneTasks = Task::forUser($user->id)->byStatus('done')->get();

        $this->assertCount(1, $todoTasks);
        $this->assertCount(1, $doneTasks);
        $this->assertEquals('todo', $todoTasks->first()->status);
        $this->assertEquals('done', $doneTasks->first()->status);
    }
}