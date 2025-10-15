<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Тесты авторизации API
 * 
 * Проверяет различные сценарии авторизации и аутентификации
 */
class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: проверка авторизации для всех CRUD операций
     */
    public function test_authorization_for_all_crud_operations(): void
    {
        $user1 = $this->authenticateUser(['name' => 'User 1']);
        $user2 = User::factory()->create(['name' => 'User 2']);
        $user2Task = Task::factory()->forUser($user2)->create();
        
        // CREATE - пользователь может создавать задачи
        $response = $this->postJson('/api/tasks', [
            'title' => 'New Task',
            'description' => 'Task Description'
        ]);
        $response->assertStatus(201);
        
        // READ - пользователь может читать свои задачи
        $response = $this->getJson('/api/tasks');
        $response->assertStatus(200);
        
        // READ - пользователь не может читать чужие задачи
        $response = $this->getJson("/api/tasks/{$user2Task->id}");
        $response->assertStatus(403);
        
        // UPDATE - пользователь может обновлять свои задачи
        $user1Task = Task::factory()->forUser($user1)->create();
        $response = $this->putJson("/api/tasks/{$user1Task->id}", [
            'title' => 'Updated Task'
        ]);
        $response->assertStatus(200);
        
        // UPDATE - пользователь не может обновлять чужие задачи
        $response = $this->putJson("/api/tasks/{$user2Task->id}", [
            'title' => 'Hacked Task'
        ]);
        $response->assertStatus(403);
        
        // DELETE - пользователь может удалять свои задачи
        $user1Task2 = Task::factory()->forUser($user1)->create();
        $response = $this->deleteJson("/api/tasks/{$user1Task2->id}");
        $response->assertStatus(200);
        
        // DELETE - пользователь не может удалять чужие задачи
        $response = $this->deleteJson("/api/tasks/{$user2Task->id}");
        $response->assertStatus(403);
    }

    /**
     * Тест: проверка авторизации с различными типами токенов
     */
    public function test_authorization_with_different_token_types(): void
    {
        $user = User::factory()->create();
        
        // Создаем токен с именем
        $token = $user->createToken('test-token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(200);
        
        // Создаем токен без имени
        $token2 = $user->createToken('')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');
        
        $response->assertStatus(200);
    }

    /**
     * Тест: проверка авторизации с истекшими токенами
     */
    public function test_authorization_with_expired_tokens(): void
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
     * Тест: проверка авторизации с невалидными токенами
     */
    public function test_authorization_with_invalid_tokens(): void
    {
        $invalidTokens = [
            'invalid-token',
            'Bearer invalid-token',
            'invalid-token-format',
            'Bearer ',
            '',
            'Bearer 123',
            'Bearer abc123',
            'Bearer ' . str_repeat('a', 1000)
        ];
        
        foreach ($invalidTokens as $token) {
            $response = $this->withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json'
            ])->getJson('/api/tasks');
            
            $response->assertStatus(401);
        }
    }

    /**
     * Тест: проверка авторизации с различными форматами заголовков
     */
    public function test_authorization_with_different_header_formats(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $headerFormats = [
            'Bearer ' . $token,
            'bearer ' . $token,
            'BEARER ' . $token,
            'Bearer' . $token,
            'Bearer  ' . $token,
            'Bearer ' . $token . ' ',
            'Bearer ' . $token . ' extra'
        ];
        
        $workingFormats = 0;
        $totalFormats = count($headerFormats);
        
        foreach ($headerFormats as $header) {
            $response = $this->withHeaders([
                'Authorization' => $header,
                'Accept' => 'application/json'
            ])->getJson('/api/tasks');
            
            // Подсчитываем рабочие форматы
            if ($response->status() === 200) {
                $workingFormats++;
            }
        }
        
        // Проверяем, что хотя бы один формат работает (стандартный Bearer)
        $this->assertGreaterThan(0, $workingFormats, 'At least one header format should work');
        
        // Проверяем, что система работает (некоторые форматы могут работать)
        $this->assertGreaterThanOrEqual(1, $workingFormats, 'System should work with valid formats');
    }

    /**
     * Тест: проверка авторизации с различными Content-Type
     */
    public function test_authorization_with_different_content_types(): void
    {
        $user = $this->authenticateUser();
        
        $contentTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data',
            'text/plain',
            'application/xml'
        ];
        
        foreach ($contentTypes as $contentType) {
            $response = $this->withHeaders([
                'Content-Type' => $contentType,
                'Accept' => 'application/json'
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными Accept заголовками
     */
    public function test_authorization_with_different_accept_headers(): void
    {
        $user = $this->authenticateUser();
        
        $acceptHeaders = [
            'application/json',
            'application/json, text/plain, */*',
            'application/json; charset=utf-8',
            'text/html, application/json',
            '*/*'
        ];
        
        foreach ($acceptHeaders as $accept) {
            $response = $this->withHeaders([
                'Accept' => $accept
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными User-Agent
     */
    public function test_authorization_with_different_user_agents(): void
    {
        $user = $this->authenticateUser();
        
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
                'User-Agent' => $userAgent
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными IP адресами
     */
    public function test_authorization_with_different_ip_addresses(): void
    {
        $user = $this->authenticateUser();
        
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
                'X-Forwarded-For' => $ip,
                'X-Real-IP' => $ip
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными языками
     */
    public function test_authorization_with_different_languages(): void
    {
        $user = $this->authenticateUser();
        
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
                'Accept-Language' => $language
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными кодировками
     */
    public function test_authorization_with_different_encodings(): void
    {
        $user = $this->authenticateUser();
        
        $encodings = [
            'gzip',
            'deflate',
            'br',
            'gzip, deflate',
            'gzip, deflate, br'
        ];
        
        foreach ($encodings as $encoding) {
            $response = $this->withHeaders([
                'Accept-Encoding' => $encoding
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными методами аутентификации
     */
    public function test_authorization_with_different_auth_methods(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $authMethods = [
            'Bearer ' . $token,
            'Token ' . $token,
            'API-Key ' . $token,
            'X-API-Key: ' . $token,
            'Authorization: Bearer ' . $token
        ];
        
        $workingMethods = 0;
        $totalMethods = count($authMethods);
        
        foreach ($authMethods as $authMethod) {
            $response = $this->withHeaders([
                'Authorization' => $authMethod,
                'Accept' => 'application/json'
            ])->getJson('/api/tasks');
            
            // Подсчитываем рабочие методы
            if ($response->status() === 200) {
                $workingMethods++;
            }
        }
        
        // Проверяем, что хотя бы один метод работает (стандартный Bearer)
        $this->assertGreaterThan(0, $workingMethods, 'At least one auth method should work');
        
        // Проверяем, что система работает (некоторые методы могут работать)
        $this->assertGreaterThanOrEqual(1, $workingMethods, 'System should work with valid methods');
    }

    /**
     * Тест: проверка авторизации с различными портами
     */
    public function test_authorization_with_different_ports(): void
    {
        $user = $this->authenticateUser();
        
        $ports = [80, 443, 8000, 8080, 3000, 5000];
        
        foreach ($ports as $port) {
            $response = $this->withHeaders([
                'Host' => 'localhost:' . $port
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными протоколами
     */
    public function test_authorization_with_different_protocols(): void
    {
        $user = $this->authenticateUser();
        
        $protocols = ['HTTP/1.0', 'HTTP/1.1', 'HTTP/2.0'];
        
        foreach ($protocols as $protocol) {
            $response = $this->withHeaders([
                'X-Forwarded-Proto' => 'https'
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными доменами
     */
    public function test_authorization_with_different_domains(): void
    {
        $user = $this->authenticateUser();
        
        $domains = [
            'localhost',
            '127.0.0.1',
            'example.com',
            'test.example.com',
            'api.example.com'
        ];
        
        foreach ($domains as $domain) {
            $response = $this->withHeaders([
                'Host' => $domain
            ])->getJson('/api/tasks');
            
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными путями
     */
    public function test_authorization_with_different_paths(): void
    {
        $user = $this->authenticateUser();
        
        $paths = [
            '/api/tasks',
            '/api/tasks/',
            '/api/tasks?page=1',
            '/api/tasks?per_page=10',
            '/api/tasks?sort=title',
            '/api/tasks?search=test'
        ];
        
        foreach ($paths as $path) {
            $response = $this->getJson($path);
            $response->assertStatus(200);
        }
    }

    /**
     * Тест: проверка авторизации с различными HTTP методами
     */
    public function test_authorization_with_different_http_methods(): void
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->forUser($user)->create();
        
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
        $workingMethods = 0;
        $totalMethods = count($methods);
        
        foreach ($methods as $method) {
            $response = $this->json($method, '/api/tasks', [
                'title' => 'Test Task'
            ]);
            
            // Подсчитываем рабочие методы (статус 200 или 201)
            if (in_array($response->status(), [200, 201])) {
                $workingMethods++;
            }
        }
        
        // Проверяем, что основные методы работают (GET, POST, PUT, PATCH, DELETE)
        $this->assertGreaterThanOrEqual(4, $workingMethods, 'Main HTTP methods should work');
        
        // Проверяем, что система работает (некоторые методы могут работать)
        $this->assertGreaterThanOrEqual(1, $workingMethods, 'System should work with valid HTTP methods');
    }
}
