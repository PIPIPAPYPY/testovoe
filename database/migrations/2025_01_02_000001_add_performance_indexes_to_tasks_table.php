<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Составной индекс для быстрого поиска по пользователю и статусу
            $table->index(['user_id', 'status'], 'tasks_user_status_index');
            
            // Составной индекс для поиска по пользователю и дате создания
            $table->index(['user_id', 'created_at'], 'tasks_user_created_index');
            
            // Индекс для поиска по приоритету
            $table->index('priority', 'tasks_priority_index');
            
            // Индекс для поиска по дедлайну
            $table->index('deadline', 'tasks_deadline_index');
            
            // Полнотекстовый индекс для поиска по title и description
            $table->index(['title', 'description'], 'tasks_search_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_user_status_index');
            $table->dropIndex('tasks_user_created_index');
            $table->dropIndex('tasks_priority_index');
            $table->dropIndex('tasks_deadline_index');
            $table->dropIndex('tasks_search_index');
        });
    }
};


