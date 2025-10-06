<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    public function test_user_can_authenticate_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ],
                    'token'
                ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_user_cannot_authenticate_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        // Неправильный пароль
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);

        // Несуществующий email
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(401);
    }

    public function test_authentication_requires_email_and_password(): void
    {
        // Без email
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123'
        ]);

        $this->assertValidationError($response, ['email']);

        // Без password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com'
        ]);

        $this->assertValidationError($response, ['password']);
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ],
                    'token'
                ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $this->assertValidationError($response, ['email']);
    }

    public function test_registration_validates_required_fields(): void
    {
        // Без имени
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $this->assertValidationError($response, ['name']);

        // Без email
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $this->assertValidationError($response, ['email']);

        // Без пароля
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->assertValidationError($response, ['password']);
    }

    public function test_registration_validates_password_length(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123', // Слишком короткий пароль
            'password_confirmation' => '123'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $this->assertValidationError($response, ['password']);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->authenticateUser();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Успешный выход'
                ]);

        // Проверяем, что токен был удален
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = $this->authenticateUser([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com'
                ])
                ->assertJsonMissing([
                    'password'
                ]);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_token_authentication_works_with_bearer_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');

        $response->assertStatus(200);
    }

    public function test_invalid_token_returns_unauthorized(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');

        $response->assertStatus(401);
    }

    public function test_expired_token_returns_unauthorized(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Удаляем токен из базы данных, имитируя истечение
        $user->tokens()->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');

        $response->assertStatus(401);
    }
}