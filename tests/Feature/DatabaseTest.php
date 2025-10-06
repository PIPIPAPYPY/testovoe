<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class DatabaseTest extends TestCase
{

    public function test_database_connection_works(): void
    {
        // Простой тест подключения к базе данных
        $userCount = \App\Models\User::count();
        $this->assertIsInt($userCount);
        $this->assertGreaterThanOrEqual(0, $userCount);
    }

    public function test_user_factory_works(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $this->assertEquals('Test User', $user->name);
    }

    public function test_user_creation_and_deletion(): void
    {
        // Получаем начальное количество пользователей
        $initialCount = User::count();
        
        // Создаем пользователя
        $user = User::factory()->create();
        $this->assertEquals($initialCount + 1, User::count());

        // Удаляем пользователя
        $user->delete();
        $this->assertEquals($initialCount, User::count());
    }
}