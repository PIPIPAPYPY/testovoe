<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;

class WorkingTaskTest extends TestCase
{
    public function test_task_creation_works(): void
    {
        $user = User::factory()->create();
        
        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'user_id' => $user->id
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $user->id
        ]);
    }

    public function test_task_relationships(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $task->user_id);
        $this->assertInstanceOf(User::class, $task->user);
        $this->assertTrue($user->tasks->contains($task));
    }

    public function test_task_scopes(): void
    {
        $user = User::factory()->create();
        
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_TODO]);
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_DONE]);

        $todoTasks = Task::forUser($user->id)->byStatus(Task::STATUS_TODO)->get();
        $doneTasks = Task::forUser($user->id)->byStatus(Task::STATUS_DONE)->get();

        $this->assertCount(1, $todoTasks);
        $this->assertCount(1, $doneTasks);
    }
}