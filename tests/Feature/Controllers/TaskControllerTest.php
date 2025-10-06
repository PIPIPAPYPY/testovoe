<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;

class TaskControllerTest extends TestCase
{
    public function test_user_can_create_task_with_valid_data(): void
    {
        $user = $this->authenticateUser();
        
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => Task::PRIORITY_HIGH,
            'status' => Task::STATUS_TODO,
            'deadline' => now()->addDays(7)->format('Y-m-d H:i:s')
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'deadline',
                        'created_at',
                        'updated_at',
                        'status_label',
                        'priority_label',
                        'is_overdue'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'title' => 'Test Task',
                        'description' => 'Test Description',
                        'priority' => Task::PRIORITY_HIGH,
                        'status' => Task::STATUS_TODO
                    ]
                ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'user_id' => $user->id,
            'priority' => Task::PRIORITY_HIGH,
            'status' => Task::STATUS_TODO
        ]);
    }

    public function test_task_creation_fails_with_invalid_data(): void
    {
        $this->authenticateUser();

        // Тест без обязательного поля title
        $response = $this->postJson('/api/tasks', [
            'description' => 'Test Description'
        ]);

        $this->assertValidationError($response, ['title']);

        // Тест с невалидным приоритетом
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'priority' => 5
        ]);

        $this->assertValidationError($response, ['priority']);

        // Тест с невалидным статусом
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'status' => 'invalid_status'
        ]);

        $this->assertValidationError($response, ['status']);

        // Тест с дедлайном в прошлом
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'deadline' => now()->subDays(1)->format('Y-m-d H:i:s')
        ]);

        $this->assertValidationError($response, ['deadline']);
    }

    public function test_user_can_view_their_tasks(): void
    {
        $user = $this->authenticateUser();
        $otherUser = User::factory()->create();
        
        // Создаем задачи для текущего пользователя
        $userTasks = Task::factory()->count(3)->forUser($user)->create();
        
        // Создаем задачи для другого пользователя (не должны отображаться)
        Task::factory()->count(2)->forUser($otherUser)->create();

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'status',
                            'priority',
                            'deadline',
                            'created_at',
                            'updated_at',
                            'status_label',
                            'priority_label',
                            'is_overdue'
                        ]
                    ]
                ]);

        $responseData = $response->json('data');
        $this->assertCount(3, $responseData);
        
        // Проверяем, что получили правильное количество задач
        // (проверка принадлежности пользователю происходит на уровне контроллера)
    }

    public function test_user_can_view_specific_task(): void
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->forUser($user)->create();

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'deadline',
                        'created_at',
                        'updated_at',
                        'status_label',
                        'priority_label',
                        'is_overdue'
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $task->id,
                        'title' => $task->title
                    ]
                ]);
    }

    public function test_user_cannot_view_other_users_task(): void
    {
        $this->authenticateUser();
        $otherUser = User::factory()->create();
        $otherUserTask = Task::factory()->forUser($otherUser)->create();

        $response = $this->getJson("/api/tasks/{$otherUserTask->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_task_with_valid_data(): void
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->forUser($user)->create([
            'title' => 'Original Title',
            'status' => Task::STATUS_TODO
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => Task::STATUS_IN_PROGRESS,
            'priority' => Task::PRIORITY_HIGH
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $task->id,
                        'title' => 'Updated Title',
                        'status' => Task::STATUS_IN_PROGRESS,
                        'priority' => Task::PRIORITY_HIGH
                    ]
                ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => Task::STATUS_IN_PROGRESS,
            'priority' => Task::PRIORITY_HIGH
        ]);
    }

    public function test_task_update_fails_with_invalid_data(): void
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->forUser($user)->create();

        // Тест с невалидным приоритетом
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'priority' => 10
        ]);

        $this->assertValidationError($response, ['priority']);

        // Тест с невалидным статусом
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'status' => 'invalid_status'
        ]);

        $this->assertValidationError($response, ['status']);
    }

    public function test_user_cannot_update_other_users_task(): void
    {
        $this->authenticateUser();
        $otherUser = User::factory()->create();
        $otherUserTask = Task::factory()->forUser($otherUser)->create();

        $response = $this->putJson("/api/tasks/{$otherUserTask->id}", [
            'title' => 'Hacked Title'
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_task(): void
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->forUser($user)->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Задача успешно удалена'
                ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    public function test_user_cannot_delete_other_users_task(): void
    {
        $this->authenticateUser();
        $otherUser = User::factory()->create();
        $otherUserTask = Task::factory()->forUser($otherUser)->create();

        $response = $this->deleteJson("/api/tasks/{$otherUserTask->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $otherUserTask->id
        ]);
    }

    public function test_accessing_non_existent_task_returns_404(): void
    {
        $this->authenticateUser();

        $response = $this->getJson('/api/tasks/99999');

        $response->assertStatus(404);
    }

    public function test_user_can_filter_tasks_by_status(): void
    {
        $user = $this->authenticateUser();
        
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_TODO]);
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_IN_PROGRESS]);
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_DONE]);

        $response = $this->getJson('/api/tasks?status=todo');

        $response->assertStatus(200);
        $tasks = $response->json('data');
        
        $this->assertCount(1, $tasks);
        $this->assertEquals(Task::STATUS_TODO, $tasks[0]['status']);
    }

    public function test_user_can_filter_tasks_by_priority(): void
    {
        $user = $this->authenticateUser();
        
        Task::factory()->forUser($user)->highPriority()->create();
        Task::factory()->forUser($user)->create(['priority' => Task::PRIORITY_MEDIUM]);
        Task::factory()->forUser($user)->lowPriority()->create();

        $response = $this->getJson('/api/tasks?priority=1');

        $response->assertStatus(200);
        $tasks = $response->json('data');
        
        $this->assertCount(1, $tasks);
        $this->assertEquals(Task::PRIORITY_HIGH, $tasks[0]['priority']);
    }

    public function test_user_can_search_tasks(): void
    {
        $user = $this->authenticateUser();
        
        Task::factory()->forUser($user)->create(['title' => 'Important Meeting']);
        Task::factory()->forUser($user)->create(['title' => 'Buy Groceries']);
        Task::factory()->forUser($user)->create(['description' => 'Meeting with client']);

        $response = $this->getJson('/api/tasks?search=meeting');

        $response->assertStatus(200);
        $tasks = $response->json('data');
        
        $this->assertGreaterThanOrEqual(1, count($tasks));
    }
}