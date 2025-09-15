<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

/**
 * Сидер для создания тестового пользователя
 * 
 * Создает пользователя для тестирования функциональности приложения
 */
class TestUserSeeder extends Seeder
{
    /**
     * Выполнить заполнение базы данных тестовыми данными
     * @return void
     */
    public function run(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
