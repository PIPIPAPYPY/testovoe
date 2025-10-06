<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SimpleTaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_create_task(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'title' => 'API Test Task',
            'description' => 'Created via API'
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['title' => 'API Test Task']);

        $this->assertDatabaseHas('tasks', [
            'title' => 'API Test Task',
            'user_id' => $user->id
        ]);
    }

    public function test_authenticated_user_can_view_tasks(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Task::factory()->forUser($user)->create(['title' => 'My Task']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'My Task']);
    }

    public function test_user_can_update_their_task(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->forUser($user)->create(['title' => 'Original Title']);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Updated Title']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title'
        ]);
    }

    public function test_user_can_delete_their_task(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::factory()->forUser($user)->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    public function test_task_creation_requires_title(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'description' => 'Task without title'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }
}