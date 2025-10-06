<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;

class SimpleTaskTest extends TestCase
{
    public function test_task_can_be_created_with_valid_data(): void
    {
        $user = User::factory()->create();
        
        $task = Task::factory()->forUser($user)->create([
            'title' => 'Test Task',
            'description' => 'Test Description'
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'user_id' => $user->id
        ]);
    }

    public function test_task_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $task->user_id);
        $this->assertInstanceOf(User::class, $task->user);
    }

    public function test_task_has_correct_default_values(): void
    {
        $task = new Task();
        
        $this->assertEquals('todo', $task->status);
        $this->assertEquals(2, $task->priority);
    }
}