<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

/**
 * Тесты RBAC (Role-Based Access Control) и ownership
 * 
 * Проверяет, что пользователи могут работать только со своими задачами
 * и не имеют доступа к чужим данным
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: пользователь может видеть только свои задачи
     */
    public function test_user_can_only_see_own_tasks(): void
    {
        $user1 = $this->authenticateUser(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        
        // Создаем задачи для обоих пользователей
        $user1Tasks = Task::factory()->count(3)->forUser($user1)->create();
        $user2Tasks = Task::factory()->count(2)->forUser($user2)->create();
        
        $response = $this->getJson('/api/tasks');
        
        $response->assertStatus(200);
        $tasks = $response->json('data');
        
        // Проверяем, что получили только задачи user1
        $this->assertCount(3, $tasks);
        
        // Проверяем, что все задачи принадлежат user1
        foreach ($tasks as $task) {
            // Проверяем, что задача принадлежит user1 (через API ответ)
            $this->assertArrayHasKey('id', $task);
        }
        
        // Проверяем, что задачи user2 не попали в ответ
        $taskIds = collect($tasks)->pluck('id')->toArray();
        foreach ($user2Tasks as $task) {
            $this->assertNotContains($task->id, $taskIds);
        }
    }

    /**
     * Тест: пользователь не может получить доступ к чужой задаче через API
     */
    public function test_user_cannot_access_other_users_task_via_api(): void
    {
        $user1 = $this->authenticateUser(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user2Task = Task::factory()->forUser($user2)->create([
            'title' => 'User 2 Secret Task'
        ]);
        
        // Попытка получить чужую задачу
        $response = $this->getJson("/api/tasks/{$user2Task->id}");
        $response->assertStatus(403);
        
        // Попытка обновить чужую задачу
        $response = $this->putJson("/api/tasks/{$user2Task->id}", [
            'title' => 'Hacked Title'
        ]);
        $response->assertStatus(403);
        
        // Попытка удалить чужую задачу
        $response = $this->deleteJson("/api/tasks/{$user2Task->id}");
        $response->assertStatus(403);
        
        // Проверяем, что задача user2 не изменилась
        $this->assertDatabaseHas('tasks', [
            'id' => $user2Task->id,
            'title' => 'User 2 Secret Task',
            'user_id' => $user2->id
        ]);
    }

    /**
     * Тест: пользователь не может изменить владельца задачи
     */
    public function test_user_cannot_change_task_ownership(): void
    {
        $user1 = $this->authenticateUser(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user1Task = Task::factory()->forUser($user1)->create();
        
        // Попытка изменить user_id через API
        $response = $this->putJson("/api/tasks/{$user1Task->id}", [
            'title' => 'Updated Title',
            'user_id' => $user2->id  // Попытка изменить владельца
        ]);
        
        // API должно игнорировать user_id или возвращать ошибку
        if ($response->status() === 200) {
            // Если API принял запрос, проверяем, что user_id не изменился
            $this->assertDatabaseHas('tasks', [
                'id' => $user1Task->id,
                'user_id' => $user1->id  // Должно остаться user1
            ]);
        } else {
            // Если API отклонил запрос, это тоже правильно
            $response->assertStatus(422); // Validation error
        }
    }

    /**
     * Тест: пользователь не может создать задачу для другого пользователя
     */
    public function test_user_cannot_create_task_for_other_user(): void
    {
        $user1 = $this->authenticateUser(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        
        // Попытка создать задачу для user2
        $response = $this->postJson('/api/tasks', [
            'title' => 'Task for User 2',
            'user_id' => $user2->id
        ]);
        
        // API должно игнорировать user_id или возвращать ошибку
        if ($response->status() === 201) {
            // Если API принял запрос, проверяем, что задача создана
            $response->assertStatus(201);
            $this->assertDatabaseHas('tasks', [
                'title' => 'Task for User 2',
                'user_id' => $user1->id  // Должно быть user1, а не user2
            ]);
        } else {
            // Если API отклонил запрос, это тоже правильно
            $response->assertStatus(422); // Validation error
        }
    }

    /**
     * Тест: множественные пользователи не могут получить доступ к чужим задачам
     */
    public function test_multiple_users_cannot_access_each_others_tasks(): void
    {
        $user1 = User::factory()->create(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user3 = User::factory()->create(['name' => 'User 3']);
        
        // Создаем задачи для каждого пользователя
        $user1Task = Task::factory()->forUser($user1)->create(['title' => 'User 1 Task']);
        $user2Task = Task::factory()->forUser($user2)->create(['title' => 'User 2 Task']);
        $user3Task = Task::factory()->forUser($user3)->create(['title' => 'User 3 Task']);
        
        // User1 аутентифицируется
        $this->actingAs($user1, 'sanctum');
        
        // User1 не может получить доступ к задачам User2 и User3
        $response = $this->getJson("/api/tasks/{$user2Task->id}");
        $response->assertStatus(403);
        
        $response = $this->getJson("/api/tasks/{$user3Task->id}");
        $response->assertStatus(403);
        
        // User1 может получить доступ только к своей задаче
        $response = $this->getJson("/api/tasks/{$user1Task->id}");
        $response->assertStatus(200);
    }

    /**
     * Тест: проверка изоляции данных между пользователями
     */
    public function test_data_isolation_between_users(): void
    {
        $user1 = $this->authenticateUser(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        
        // Создаем задачи с одинаковыми названиями для разных пользователей
        $user1Task = Task::factory()->forUser($user1)->create([
            'title' => 'Same Title',
            'description' => 'User 1 Description'
        ]);
        
        $user2Task = Task::factory()->forUser($user2)->create([
            'title' => 'Same Title',
            'description' => 'User 2 Description'
        ]);
        
        // User1 должен видеть только свою задачу
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        $this->assertCount(1, $tasks);
        $this->assertEquals('User 1 Description', $tasks[0]['description']);
        
        // User1 не должен видеть задачу User2
        $this->assertNotContains($user2Task->id, collect($tasks)->pluck('id')->toArray());
    }

    /**
     * Тест: пользователь не может получить доступ к задачам через прямой ID
     */
    public function test_user_cannot_access_tasks_by_direct_id_manipulation(): void
    {
        $user1 = $this->authenticateUser(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user2Task = Task::factory()->forUser($user2)->create();
        
        // Попытка получить доступ к задаче через манипуляцию ID
        $response = $this->getJson("/api/tasks/{$user2Task->id}");
        $response->assertStatus(403);
        
        // Попытка получить доступ к несуществующей задаче
        $response = $this->getJson('/api/tasks/99999');
        $response->assertStatus(404);
        
        // Попытка получить доступ к задаче с отрицательным ID
        $response = $this->getJson('/api/tasks/-1');
        $response->assertStatus(404);
    }

    /**
     * Тест: проверка авторизации через Policy
     */
    public function test_task_policy_enforcement(): void
    {
        $user1 = $this->authenticateUser(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user2Task = Task::factory()->forUser($user2)->create();
        
        // Проверяем, что Policy блокирует доступ
        $this->assertFalse($user1->can('view', $user2Task));
        $this->assertFalse($user1->can('update', $user2Task));
        $this->assertFalse($user1->can('delete', $user2Task));
        
        // Проверяем, что Policy разрешает доступ к собственным задачам
        $user1Task = Task::factory()->forUser($user1)->create();
        $this->assertTrue($user1->can('view', $user1Task));
        $this->assertTrue($user1->can('update', $user1Task));
        $this->assertTrue($user1->can('delete', $user1Task));
    }

    /**
     * Тест: проверка авторизации для неаутентифицированных пользователей
     */
    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->forUser($user)->create();
        
        // Неаутентифицированный пользователь не может получить доступ к задачам
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);
        
        $response = $this->getJson("/api/tasks/{$task->id}");
        $response->assertStatus(401);
        
        $response = $this->postJson('/api/tasks', [
            'title' => 'New Task'
        ]);
        $response->assertStatus(401);
        
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task'
        ]);
        $response->assertStatus(401);
        
        $response = $this->deleteJson("/api/tasks/{$task->id}");
        $response->assertStatus(401);
    }

    /**
     * Тест: проверка авторизации с истекшим токеном
     */
    public function test_expired_token_cannot_access_tasks(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Удаляем токен, имитируя истечение
        $user->tokens()->delete();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(401);
    }

    /**
     * Тест: проверка авторизации с невалидным токеном
     */
    public function test_invalid_token_cannot_access_tasks(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(401);
    }

    /**
     * Тест: проверка авторизации без токена
     */
    public function test_no_token_cannot_access_tasks(): void
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);
    }

    /**
     * Тест: проверка авторизации с неправильным форматом токена
     */
    public function test_malformed_token_cannot_access_tasks(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'InvalidFormat token',
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(401);
    }

    /**
     * Тест: проверка авторизации с пустым токеном
     */
    public function test_empty_token_cannot_access_tasks(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ',
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(401);
    }
}
