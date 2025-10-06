<?php

namespace Tests\Feature\ErrorHandling;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApiEdgeCaseTest extends TestCase
{
    public function test_api_handles_malformed_json(): void
    {
        $user = $this->authenticateUser();

        // Отправляем невалидный JSON
        $response = $this->withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken
        ])->call('POST', '/api/tasks', [], [], [], [], '{"title": "Test", "invalid": json}');

        // Laravel может вернуть 422 из-за невалидного JSON, 400 или даже 302
        $this->assertTrue(in_array($response->getStatusCode(), [302, 400, 422]));
    }

    public function test_api_handles_empty_request_body(): void
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/tasks', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title']);
    }

    public function test_api_handles_null_values(): void
    {
        $user = $this->authenticateUser();

        $response = $this->postJson('/api/tasks', [
            'title' => null,
            'description' => null,
            'priority' => null,
            'status' => null,
            'deadline' => null
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title']);
    }

    public function test_api_handles_very_large_requests(): void
    {
        $user = $this->authenticateUser();

        // Создаем очень большой запрос
        $largeData = [
            'title' => 'Test Task',
            'description' => str_repeat('A very long description. ', 10000), // ~250KB
        ];

        $response = $this->postJson('/api/tasks', $largeData);

        // Должен вернуть ошибку валидации из-за превышения лимита описания
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['description']);
    }

    public function test_api_handles_concurrent_requests(): void
    {
        $user = $this->authenticateUser();

        // Создаем задачу
        $task = Task::factory()->forUser($user)->create([
            'title' => 'Original Title'
        ]);

        // Симулируем одновременные обновления
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->putJson("/api/tasks/{$task->id}", [
                'title' => "Updated Title {$i}"
            ]);
        }

        // Все запросы должны быть успешными
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Проверяем финальное состояние
        $task->refresh();
        $this->assertStringContainsString('Updated Title', $task->title);
    }

    public function test_api_handles_database_connection_errors(): void
    {
        $user = $this->authenticateUser();

        // Временно "ломаем" подключение к БД
        DB::disconnect();
        
        try {
            $response = $this->postJson('/api/tasks', [
                'title' => 'Test Task'
            ]);

            // Ожидаем ошибку сервера
            $response->assertStatus(500);
        } finally {
            // Восстанавливаем подключение
            DB::reconnect();
        }
    }

    public function test_api_handles_invalid_route_parameters(): void
    {
        $user = $this->authenticateUser();

        // Тест с очень большим ID
        $response = $this->getJson('/api/tasks/999999999');
        $response->assertStatus(404);

        // Тест с нулевым ID
        $response = $this->getJson('/api/tasks/0');
        $response->assertStatus(404);
    }

    public function test_api_handles_invalid_query_parameters(): void
    {
        $user = $this->authenticateUser();
        Task::factory()->count(5)->forUser($user)->create();

        // Тест с невалидными параметрами фильтрации
        $response = $this->getJson('/api/tasks?status=invalid_status');
        $response->assertStatus(422);

        $response = $this->getJson('/api/tasks?priority=invalid_priority');
        $response->assertStatus(422);

        // Тест с невалидными параметрами сортировки
        $response = $this->getJson('/api/tasks?sort_by=invalid_field');
        $response->assertStatus(422);

        $response = $this->getJson('/api/tasks?sort_dir=invalid_direction');
        $response->assertStatus(422);

        // Тест с невалидными датами
        $response = $this->getJson('/api/tasks?created_from=invalid_date');
        $response->assertStatus(422);

        $response = $this->getJson('/api/tasks?deadline_to=not-a-date');
        $response->assertStatus(422);
    }

    public function test_api_handles_missing_content_type_header(): void
    {
        $user = $this->authenticateUser();

        // Отправляем запрос без Content-Type заголовка
        $response = $this->call('POST', '/api/tasks', [
            'title' => 'Test Task'
        ], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user->createToken('test')->plainTextToken
        ]);

        // Laravel должен обработать запрос корректно
        $response->assertStatus(201);
    }

    public function test_api_handles_invalid_http_methods(): void
    {
        $user = $this->authenticateUser();

        // Тест неподдерживаемых HTTP методов для несуществующего маршрута
        $response = $this->call('PATCH', '/api/invalid-endpoint', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $user->createToken('test')->plainTextToken,
            'CONTENT_TYPE' => 'application/json'
        ]);

        $response->assertStatus(404);
    }

    public function test_api_handles_expired_tokens(): void
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

    public function test_api_handles_malformed_authorization_header(): void
    {
        // Тест с неправильным форматом заголовка авторизации
        $response = $this->withHeaders([
            'Authorization' => 'InvalidFormat token123',
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');

        $response->assertStatus(401);

        // Тест без Bearer префикса
        $response = $this->withHeaders([
            'Authorization' => 'token123',
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');

        $response->assertStatus(401);

        // Тест с пустым токеном
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ',
            'Accept' => 'application/json'
        ])->getJson('/api/tasks');

        $response->assertStatus(401);
    }

    public function test_api_handles_rate_limiting(): void
    {
        $user = $this->authenticateUser();

        // Отправляем много запросов подряд
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->getJson('/api/tasks');
        }

        // Проверяем, что не все запросы заблокированы (зависит от настроек rate limiting)
        $successfulRequests = collect($responses)->filter(function ($response) {
            return $response->getStatusCode() === 200;
        })->count();

        $this->assertGreaterThan(0, $successfulRequests);
    }

    public function test_api_handles_memory_intensive_operations(): void
    {
        $user = $this->authenticateUser();

        // Создаем много задач
        Task::factory()->count(1000)->forUser($user)->create();

        // Запрашиваем все задачи без пагинации (если возможно)
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200);
        
        // Проверяем, что ответ содержит разумное количество задач (пагинация работает)
        $tasks = $response->json('data');
        $this->assertLessThanOrEqual(50, count($tasks)); // Лимит пагинации
    }

    public function test_api_handles_special_characters_in_urls(): void
    {
        $user = $this->authenticateUser();

        // Тест с специальными символами в URL
        $response = $this->getJson('/api/tasks?search=' . urlencode('test & special chars'));
        $response->assertStatus(200);

        $response = $this->getJson('/api/tasks?search=' . urlencode('тест на русском'));
        $response->assertStatus(200);

        $response = $this->getJson('/api/tasks?search=' . urlencode('test with "quotes"'));
        $response->assertStatus(200);
    }

    public function test_api_handles_timezone_edge_cases(): void
    {
        $user = $this->authenticateUser();

        // Тест с датами в разных часовых поясах
        $response = $this->postJson('/api/tasks', [
            'title' => 'Timezone Test',
            'deadline' => now()->addDays(30)->format('Y-m-d\TH:i:s\Z') // UTC
        ]);

        $response->assertStatus(201);

        // Тест с локальным временем
        $response = $this->postJson('/api/tasks', [
            'title' => 'Local Time Test',
            'deadline' => now()->addDays(30)->format('Y-m-d\TH:i:sP') // Moscow time
        ]);

        $response->assertStatus(201);
    }

    public function test_api_handles_duplicate_requests(): void
    {
        $user = $this->authenticateUser();

        $taskData = [
            'title' => 'Duplicate Test Task',
            'description' => 'This task might be created multiple times'
        ];

        // Отправляем одинаковые запросы
        $response1 = $this->postJson('/api/tasks', $taskData);
        $response2 = $this->postJson('/api/tasks', $taskData);

        // Оба запроса должны быть успешными (дубликаты разрешены)
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        // Проверяем, что созданы две разные задачи
        $task1Id = $response1->json('data.id');
        $task2Id = $response2->json('data.id');
        
        $this->assertNotEquals($task1Id, $task2Id);
    }
}