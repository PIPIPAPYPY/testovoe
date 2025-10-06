<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;

class SimpleApiTest extends TestCase
{
    public function test_authenticated_user_can_create_task(): void
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/tasks', [
            'title' => 'Simple Test Task'
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('tasks', [
            'title' => 'Simple Test Task',
            'user_id' => $user->id
        ]);
    }

    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $response = $this->getJson('/api/tasks');
        
        $response->assertStatus(401);
    }

    public function test_user_can_view_their_tasks(): void
    {
        $user = $this->authenticateUser();
        Task::factory()->forUser($user)->create(['title' => 'My Task']);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'My Task']);
    }
}