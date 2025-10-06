<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registration_works(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'user' => ['id', 'name', 'email'],
            'token'
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_login_works(): void
    {
        // Создаем пользователя
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'user' => ['id', 'name', 'email'],
            'token'
        ]);
    }

    public function test_invalid_login_fails(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }
}