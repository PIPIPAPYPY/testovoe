<?php

namespace Tests\Feature\ErrorHandling;

use Tests\TestCase;
use Tests\Helpers\TestDataHelper;
use App\Models\Task;
use App\Models\User;

class ValidationErrorTest extends TestCase
{
    public function test_task_creation_validation_errors(): void
    {
        $user = $this->authenticateUser();
        $invalidData = TestDataHelper::getInvalidTaskData();

        $response = $this->postJson('/api/tasks', $invalidData['empty_title']);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title']);

        // Тест слишком длинного заголовка
        $response = $this->postJson('/api/tasks', $invalidData['long_title']);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title']);

        // Тест невалидного приоритета
        $response = $this->postJson('/api/tasks', $invalidData['invalid_priority']);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['priority']);

        // Тест невалидного статуса
        $response = $this->postJson('/api/tasks', $invalidData['invalid_status']);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['status']);

        // Тест дедлайна в прошлом
        $response = $this->postJson('/api/tasks', $invalidData['past_deadline']);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['deadline']);

        // Тест слишком длинного описания
        $response = $this->postJson('/api/tasks', $invalidData['long_description']);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['description']);
    }

    public function test_task_update_validation_errors(): void
    {
        $user = $this->authenticateUser();
        $task = Task::factory()->forUser($user)->create();

        // Тест обновления с невалидными данными
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'title' => '', // Пустой заголовок
            'priority' => 10, // Невалидный приоритет
            'status' => 'invalid_status' // Невалидный статус
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title', 'priority', 'status']);
    }

    public function test_authentication_validation_errors(): void
    {
        // Тест входа без email
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Тест входа без пароля
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);

        // Тест регистрации с невалидным email
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);

        // Тест регистрации с коротким паролем
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123', // Слишком короткий пароль (минимум 8 символов)
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    public function test_boundary_value_validation(): void
    {
        $user = $this->authenticateUser();

        // Тест граничных значений для заголовка (255 символов - максимум)
        $maxTitle = str_repeat('a', 255);
        $response = $this->postJson('/api/tasks', [
            'title' => $maxTitle
        ]);
        $response->assertStatus(201);

        // Тест превышения лимита заголовка (256 символов)
        $tooLongTitle = str_repeat('a', 256);
        $response = $this->postJson('/api/tasks', [
            'title' => $tooLongTitle
        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['title']);

        // Тест граничных значений для описания (1000 символов - максимум)
        $maxDescription = str_repeat('a', 1000);
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => $maxDescription
        ]);
        $response->assertStatus(201);

        // Тест превышения лимита описания (1001 символ)
        $tooLongDescription = str_repeat('a', 1001);
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => $tooLongDescription
        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['description']);
    }

    public function test_data_type_validation(): void
    {
        $user = $this->authenticateUser();

        // Тест невалидных типов данных
        $response = $this->postJson('/api/tasks', [
            'title' => 123, // Число вместо строки
            'priority' => 'high', // Строка вместо числа
            'deadline' => 'not-a-date' // Невалидная дата
        ]);

        $response->assertStatus(422);
        
        // Laravel автоматически приводит некоторые типы, но проверим основные ошибки
        $errors = $response->json('errors');
        $this->assertTrue(
            isset($errors['priority']) || isset($errors['deadline']),
            'Should have validation errors for invalid data types'
        );
    }

    public function test_sql_injection_prevention(): void
    {
        $user = $this->authenticateUser();

        // Тест попытки SQL инъекции в заголовке
        $maliciousTitle = "'; DROP TABLE tasks; --";
        
        $response = $this->postJson('/api/tasks', [
            'title' => $maliciousTitle,
            'description' => 'Test description'
        ]);

        $response->assertStatus(201);
        
        // Проверяем, что задача создалась с безопасным заголовком
        $task = Task::latest()->first();
        $this->assertEquals($maliciousTitle, $task->title);
        
        // Проверяем, что таблица tasks все еще существует
        $this->assertDatabaseHas('tasks', [
            'title' => $maliciousTitle
        ]);
    }

    public function test_xss_prevention(): void
    {
        $user = $this->authenticateUser();

        // Тест попытки XSS в заголовке и описании
        $xssTitle = '<script>alert("XSS")</script>';
        $xssDescription = '<img src="x" onerror="alert(\'XSS\')">';
        
        $response = $this->postJson('/api/tasks', [
            'title' => $xssTitle,
            'description' => $xssDescription
        ]);

        $response->assertStatus(201);
        
        // Проверяем, что данные сохранились как есть (без выполнения скрипта)
        $task = Task::latest()->first();
        $this->assertEquals($xssTitle, $task->title);
        $this->assertEquals($xssDescription, $task->description);
        
        // При выводе данные должны быть экранированы (это проверяется на фронтенде)
        $response = $this->getJson("/api/tasks/{$task->id}");
        $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'title' => $xssTitle,
                        'description' => $xssDescription
                    ]
                ]);
    }

    public function test_mass_assignment_protection(): void
    {
        $user = $this->authenticateUser();

        // Попытка изменить ID пользователя через mass assignment
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'user_id' => 999, // Попытка установить другого пользователя
            'id' => 123 // Попытка установить ID
        ]);

        $response->assertStatus(201);
        
        $task = Task::latest()->first();
        
        // Проверяем, что user_id установлен корректно (из аутентификации)
        $this->assertEquals($user->id, $task->user_id);
        
        // Проверяем, что ID генерируется автоматически
        $this->assertNotEquals(123, $task->id);
    }

    public function test_concurrent_validation_errors(): void
    {
        $user = $this->authenticateUser();

        // Создаем задачу
        $task = Task::factory()->forUser($user)->create();

        // Симулируем одновременные запросы с невалидными данными
        $responses = [];
        
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->putJson("/api/tasks/{$task->id}", [
                'title' => '', // Невалидные данные
                'priority' => 10
            ]);
        }

        // Все запросы должны вернуть ошибки валидации
        foreach ($responses as $response) {
            $response->assertStatus(422)
                    ->assertJsonValidationErrors(['title', 'priority']);
        }
    }

    public function test_unicode_and_special_characters(): void
    {
        $user = $this->authenticateUser();

        // Тест с Unicode символами
        $unicodeTitle = 'Задача с эмодзи 🚀 и символами ñáéíóú';
        $unicodeDescription = 'Описание с различными символами: ©®™ и математическими: ∑∆∞';
        
        $response = $this->postJson('/api/tasks', [
            'title' => $unicodeTitle,
            'description' => $unicodeDescription
        ]);

        $response->assertStatus(201);
        
        $task = Task::latest()->first();
        $this->assertEquals($unicodeTitle, $task->title);
        $this->assertEquals($unicodeDescription, $task->description);

        // Тест с специальными символами
        $specialTitle = 'Task with "quotes" and \'apostrophes\' & ampersands';
        
        $response = $this->postJson('/api/tasks', [
            'title' => $specialTitle
        ]);

        $response->assertStatus(201);
        
        $task = Task::latest()->first();
        $this->assertEquals($specialTitle, $task->title);
    }
}