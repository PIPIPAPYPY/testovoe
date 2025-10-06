<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Проверяем и добавляем колонки только если их нет
            if (!Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }

            if (!Schema::hasColumn('tasks', 'category')) {
                $table->string('category', 100)->nullable();
            }

            if (!Schema::hasColumn('tasks', 'tags')) {
                $table->json('tags')->nullable();
            }

            if (!Schema::hasColumn('tasks', 'time_spent')) {
                $table->unsignedInteger('time_spent')->nullable()->comment('Время в минутах');
            }
        });

        // Добавляем индексы отдельно
        try {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index(['user_id', 'completed_at'], 'idx_user_completed');
                $table->index(['user_id', 'category'], 'idx_user_category');
                $table->index(['user_id', 'status', 'completed_at'], 'idx_user_status_completed');
            });
        } catch (Exception $e) {
            // Индексы уже существуют
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Удаляем индексы
            try {
                $table->dropIndex('idx_user_completed');
                $table->dropIndex('idx_user_category');
                $table->dropIndex('idx_user_status_completed');
            } catch (Exception $e) {
                // Индексы не существуют
            }

            // Удаляем колонки
            $columns = ['time_spent', 'tags', 'category', 'completed_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('tasks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
