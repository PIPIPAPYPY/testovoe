<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;

class WorkingApiTest extends TestCase
{
    public function test_basic_task_crud(): void
    {
        $user = $this->authenticateUser();

        // Create
        $response = $this->postJson('/api/tasks', [
            'title' => 'API Test Task'
        ]);
        $response->assertStatus(201);
        $taskId = $response->json('data.id');

        // Read
        $response = $this->getJson("/api/tasks/{$taskId}");
        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'API Test Task']);

        // Update
        $response = $this->putJson("/api/tasks/{$taskId}", [
            'title' => 'Updated Task'
        ]);
        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Updated Task']);

        // Delete
        $response = $this->deleteJson("/api/tasks/{$taskId}");
        $response->assertStatus(200);
    }

    public function test_authentication_required(): void
    {
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(401);

        $response = $this->postJson('/api/tasks', ['title' => 'Test']);
        $response->assertStatus(401);
    }

    public function test_validation_works(): void
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/tasks', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }
}