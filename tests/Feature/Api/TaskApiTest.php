<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class TaskApiTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_tasks_index(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_create_task(): void
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_view_specific_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_update_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_delete_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_tasks_with_sanctum_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Task::factory()->count(2)->forUser($user)->create();

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

        $tasks = $response->json('data');
        $this->assertCount(2, $tasks);
    }

    public function test_authenticated_user_can_create_task_with_sanctum_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $taskData = [
            'title' => 'API Test Task',
            'description' => 'Created via API',
            'priority' => Task::PRIORITY_HIGH
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJson([
                    'data' => [
                        'title' => 'API Test Task',
                        'description' => 'Created via API',
                        'priority' => Task::PRIORITY_HIGH
                    ]
                ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'API Test Task'
        ]);
    }

    public function test_user_can_only_access_their_own_tasks(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user1Task = Task::factory()->forUser($user1)->create();
        $user2Task = Task::factory()->forUser($user2)->create();

        // Аутентифицируемся как первый пользователь
        Sanctum::actingAs($user1);

        // Пытаемся получить задачу второго пользователя
        $response = $this->getJson("/api/tasks/{$user2Task->id}");
        $response->assertStatus(403);

        // Пытаемся обновить задачу второго пользователя
        $response = $this->putJson("/api/tasks/{$user2Task->id}", [
            'title' => 'Hacked Title'
        ]);
        $response->assertStatus(403);

        // Пытаемся удалить задачу второго пользователя
        $response = $this->deleteJson("/api/tasks/{$user2Task->id}");
        $response->assertStatus(403);

        // Проверяем, что можем получить свою задачу
        $response = $this->getJson("/api/tasks/{$user1Task->id}");
        $response->assertStatus(200);
    }

    public function test_api_returns_proper_json_structure_for_task_list(): void
    {
        $user = $this->authenticateUser();
        Task::factory()->count(3)->forUser($user)->create();

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
    }

    public function test_api_returns_proper_json_structure_for_single_task(): void
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
                ]);
    }

    public function test_api_supports_correct_http_methods(): void
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->forUser($user)->create();

        // GET /api/tasks (index)
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(200);

        // POST /api/tasks (store)
        $response = $this->postJson('/api/tasks', [
            'title' => 'New Task'
        ]);
        $response->assertStatus(201);

        // GET /api/tasks/{id} (show)
        $response = $this->getJson("/api/tasks/{$task->id}");
        $response->assertStatus(200);

        // PUT /api/tasks/{id} (update)
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task'
        ]);
        $response->assertStatus(200);

        // DELETE /api/tasks/{id} (destroy)
        $response = $this->deleteJson("/api/tasks/{$task->id}");
        $response->assertStatus(200);
    }

    public function test_api_validates_json_content_type(): void
    {
        $user = $this->authenticateUser();

        // Отправляем запрос без JSON content-type
        $response = $this->post('/api/tasks', [
            'title' => 'Test Task'
        ], [
            'Accept' => 'application/json'
        ]);

        // API должен принимать и обычные POST запросы
        $response->assertStatus(201);
    }

    public function test_api_returns_validation_errors_in_json_format(): void
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/tasks', [
            'description' => 'Task without title'
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors' => [
                        'title'
                    ]
                ]);
    }

    public function test_api_handles_non_existent_resources_properly(): void
    {
        $user = $this->authenticateUser();

        $response = $this->getJson('/api/tasks/99999');
        $response->assertStatus(404);

        $response = $this->putJson('/api/tasks/99999', [
            'title' => 'Updated Title'
        ]);
        $response->assertStatus(404);

        $response = $this->deleteJson('/api/tasks/99999');
        $response->assertStatus(404);
    }

    public function test_api_pagination_works_correctly(): void
    {
        $user = $this->authenticateUser();
        Task::factory()->count(60)->forUser($user)->create();

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
        
        $meta = $response->json('meta');
        $this->assertEquals(60, $meta['total']);
        $this->assertEquals(50, $meta['per_page']); // Из контроллера: 50 задач на страницу
        $this->assertEquals(2, $meta['last_page']);
        
        $data = $response->json('data');
        $this->assertCount(50, $data);
    }

    public function test_api_filtering_parameters_work(): void
    {
        $user = $this->authenticateUser();
        
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_TODO]);
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_DONE]);
        Task::factory()->forUser($user)->highPriority()->create();

        // Фильтр по статусу
        $response = $this->getJson('/api/tasks?status=todo');
        $response->assertStatus(200);
        $tasks = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($tasks));

        // Фильтр по приоритету
        $response = $this->getJson('/api/tasks?priority=1');
        $response->assertStatus(200);
        $tasks = $response->json('data');
        $this->assertGreaterThanOrEqual(1, count($tasks));
    }
}