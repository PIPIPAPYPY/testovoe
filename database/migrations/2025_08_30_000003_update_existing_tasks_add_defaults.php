<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Создаем дефолтного пользователя если его нет
        $defaultUserId = DB::table('users')->first()?->id;
        
        if (!$defaultUserId) {
            $defaultUserId = DB::table('users')->insertGetId([
                'name' => 'Default User',
                'email' => 'default@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Обновляем существующие задачи
        DB::table('tasks')->whereNull('user_id')->update([
            'user_id' => $defaultUserId,
            'priority' => 3, // Низкий приоритет по умолчанию
            'updated_at' => now(),
        ]);

        // Устанавливаем дефолтные значения для NULL полей
        DB::table('tasks')->whereNull('priority')->update([
            'priority' => 3,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // В down() можно вернуть NULL значения, но это может сломать приложение
        // Поэтому просто оставляем пустым
    }
};












