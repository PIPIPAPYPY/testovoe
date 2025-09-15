<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Основной сидер базы данных
 * 
 * Координирует выполнение всех сидеров приложения
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Выполнить заполнение базы данных приложения
     * @return void
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
