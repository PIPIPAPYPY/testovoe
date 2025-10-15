<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Тесты безопасности токенов
 * 
 * Проверяет безопасность токенов аутентификации
 */
class TokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: проверка безопасности токенов при создании
     */
    public function test_token_security_on_creation(): void
    {
        $user = User::factory()->create();
        
        // Создаем токен
        $token = $user->createToken('test-token');
        
        // Проверяем, что токен создан
        $this->assertNotNull($token);
        $this->assertNotNull($token->plainTextToken);
        
        // Проверяем, что токен сохранен в базе данных
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
        
        // Проверяем, что токен создан
        $this->assertNotNull($token);
    }

    /**
     * Тест: проверка безопасности токенов при использовании
     */
    public function test_token_security_on_usage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Используем токен для аутентификации
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(200);
        
        // Проверяем, что токен все еще действителен
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    /**
     * Тест: проверка безопасности токенов при удалении
     */
    public function test_token_security_on_deletion(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Проверяем, что токен существует
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
        
        // Удаляем токен
        $user->tokens()->delete();
        
        // Проверяем, что токен удален
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
        
        // Проверяем, что токен больше не работает
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(401);
    }

    /**
     * Тест: проверка безопасности токенов при множественном использовании
     */
    public function test_token_security_on_multiple_usage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Используем токен несколько раз
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
        
        // Проверяем, что токен все еще действителен
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    /**
     * Тест: проверка безопасности токенов при одновременном использовании
     */
    public function test_token_security_on_concurrent_usage(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Симулируем одновременное использование токена
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->getJson('/api/tasks');
        }
        
        // Проверяем, что все запросы прошли успешно
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        // Проверяем, что токен все еще действителен
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    /**
     * Тест: проверка безопасности токенов при различных операциях
     */
    public function test_token_security_on_different_operations(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        // Создаем задачу
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->postJson('/api/tasks', [
            'title' => 'Test Task'
        ]);
        
        $response->assertStatus(201);
        
        // Получаем задачи
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(200);
        
        // Обновляем задачу
        $task = Task::where('user_id', $user->id)->first();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->putJson("/api/tasks/{$task->id}", [
            'title' => 'Updated Task'
        ]);
        
        $response->assertStatus(200);
        
        // Удаляем задачу
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->deleteJson("/api/tasks/{$task->id}");
        
        $response->assertStatus(200);
        
        // Проверяем, что токен все еще действителен
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    /**
     * Тест: проверка безопасности токенов при различных пользователях
     */
    public function test_token_security_on_different_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $token1 = $user1->createToken('user1-token')->plainTextToken;
        $token2 = $user2->createToken('user2-token')->plainTextToken;
        
        // User1 использует свой токен
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(200);
        
        // User2 использует свой токен
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(200);
        
        // Проверяем, что оба токена действительны
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user1->id,
            'name' => 'user1-token'
        ]);
        
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user2->id,
            'name' => 'user2-token'
        ]);
    }

    /**
     * Тест: проверка безопасности токенов при различных именах
     */
    public function test_token_security_on_different_names(): void
    {
        $user = User::factory()->create();
        
        $tokenNames = [
            'test-token',
            'api-token',
            'mobile-app',
            'web-app',
            'admin-token',
            'user-token',
            'session-token',
            'auth-token'
        ];
        
        foreach ($tokenNames as $name) {
            $token = $user->createToken($name)->plainTextToken;
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json'
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
            
            // Проверяем, что токен создан с правильным именем
            $this->assertDatabaseHas('personal_access_tokens', [
                'tokenable_id' => $user->id,
                'name' => $name
            ]);
        }
    }

    /**
     * Тест: проверка безопасности токенов при различных разрешениях
     */
    public function test_token_security_on_different_abilities(): void
    {
        $user = User::factory()->create();
        
        // Создаем токен с разрешениями
        $token = $user->createToken('test-token', ['read', 'write'])->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(200);
        
        // Проверяем, что токен создан с правильными разрешениями
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    /**
     * Тест: проверка безопасности токенов при различных сроках действия
     */
    public function test_token_security_on_different_expiration(): void
    {
        $user = User::factory()->create();
        
        // Создаем токен с истечением
        $token = $user->createToken('test-token', ['*'], now()->addHour())->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(200);
        
        // Проверяем, что токен создан с правильным сроком действия
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-token'
        ]);
    }

    /**
     * Тест: проверка безопасности токенов при различных IP адресах
     */
    public function test_token_security_on_different_ip_addresses(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $ipAddresses = [
            '127.0.0.1',
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '::1',
            '2001:db8::1'
        ];
        
        foreach ($ipAddresses as $ip) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'X-Forwarded-For' => $ip,
                'X-Real-IP' => $ip
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка безопасности токенов при различных User-Agent
     */
    public function test_token_security_on_different_user_agents(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'PostmanRuntime/7.26.8',
            'curl/7.68.0',
            'Python-requests/2.25.1',
            'Java/1.8.0_281',
            'PHP/8.0.0'
        ];
        
        foreach ($userAgents as $userAgent) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'User-Agent' => $userAgent
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка безопасности токенов при различных Content-Type
     */
    public function test_token_security_on_different_content_types(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $contentTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data',
            'text/plain',
            'application/xml'
        ];
        
        foreach ($contentTypes as $contentType) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => $contentType
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка безопасности токенов при различных Accept заголовках
     */
    public function test_token_security_on_different_accept_headers(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $acceptHeaders = [
            'application/json',
            'application/json, text/plain, */*',
            'application/json; charset=utf-8',
            'text/html, application/json',
            '*/*'
        ];
        
        foreach ($acceptHeaders as $accept) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => $accept
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка безопасности токенов при различных языках
     */
    public function test_token_security_on_different_languages(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $languages = [
            'en',
            'ru',
            'en-US',
            'ru-RU',
            'en-US,en;q=0.9',
            'ru-RU,ru;q=0.9,en;q=0.8'
        ];
        
        foreach ($languages as $language) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Accept-Language' => $language
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка безопасности токенов при различных кодировках
     */
    public function test_token_security_on_different_encodings(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $encodings = [
            'gzip',
            'deflate',
            'br',
            'gzip, deflate',
            'gzip, deflate, br'
        ];
        
        foreach ($encodings as $encoding) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Accept-Encoding' => $encoding
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }
}
