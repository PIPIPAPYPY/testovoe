<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Оптимизация индексов таблицы tasks
     * 
     * Проблемы, которые исправляются:
     * 1. Удаление избыточных индексов: index('status') и index('created_at') 
     *    - избыточны, так как есть составные индексы (user_id, status) и (user_id, created_at)
     *    - все запросы фильтруются по user_id
     * 2. Улучшение неоптимальных индексов:
     *    - index('priority') -> index(['user_id', 'priority'])
     *    - index('deadline') -> index(['user_id', 'deadline'])
     * 3. Удаление бесполезного индекса для поиска:
     *    - index(['title', 'description']) не помогает для LIKE '%...%' запросов
     * 4. Проверка индексов на completed_at и category:
     *    - оставляем только если они реально используются в запросах
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Удаляем избыточные одиночные индексы
            // Они избыточны, так как все запросы фильтруются по user_id
            // и есть составные индексы (user_id, status) и (user_id, created_at)
            // Laravel автоматически генерирует имена индексов как tasks_{column}_index
            $indexesToDrop = [
                'tasks_status_index',      // из create_tasks_table
                'tasks_created_at_index',  // из create_tasks_table
            ];
            
            foreach ($indexesToDrop as $indexName) {
                try {
                    $table->dropIndex($indexName);
                } catch (\Exception $e) {
                    // Индекс может не существовать или иметь другое имя
                    // Пробуем альтернативные имена (для разных СУБД)
                    $alternatives = [
                        str_replace('tasks_', '', $indexName),
                        str_replace('_index', '', $indexName),
                    ];
                    foreach ($alternatives as $altName) {
                        try {
                            $table->dropIndex($altName);
                            break;
                        } catch (\Exception $e2) {
                            // Продолжаем попытки
                        }
                    }
                }
            }

            // Удаляем неоптимальный индекс для поиска
            // LIKE '%...%' запросы не могут использовать B-tree индексы эффективно
            try {
                $table->dropIndex('tasks_search_index');
            } catch (\Exception $e) {
                // Индекс может не существовать
            }

            // Удаляем одиночные индексы на priority и deadline
            // Они будут заменены на составные с user_id
            try {
                $table->dropIndex('tasks_priority_index');
            } catch (\Exception $e) {
                // Индекс может не существовать
            }

            try {
                $table->dropIndex('tasks_deadline_index');
            } catch (\Exception $e) {
                // Индекс может не существовать
            }

            // Удаляем неиспользуемые индексы на completed_at и category
            // Эти поля не используются в запросах (category есть в фильтрах, но не реализован)
            try {
                $table->dropIndex('idx_user_completed');
            } catch (\Exception $e) {
                // Индекс может не существовать
            }

            try {
                $table->dropIndex('idx_user_category');
            } catch (\Exception $e) {
                // Индекс может не существовать
            }

            try {
                $table->dropIndex('idx_user_status_completed');
            } catch (\Exception $e) {
                // Индекс может не существовать
            }

            // Создаем оптимизированные составные индексы
            // Все запросы начинаются с WHERE user_id = ?, поэтому user_id должен быть первым
            $table->index(['user_id', 'priority'], 'tasks_user_priority_index');
            $table->index(['user_id', 'deadline'], 'tasks_user_deadline_index');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Удаляем оптимизированные индексы
            try {
                $table->dropIndex('tasks_user_priority_index');
            } catch (\Exception $e) {
            }

            try {
                $table->dropIndex('tasks_user_deadline_index');
            } catch (\Exception $e) {
            }

            // Восстанавливаем старые индексы (если они были)
            $table->index('status', 'tasks_status_index');
            $table->index('created_at', 'tasks_created_at_index');
            $table->index('priority', 'tasks_priority_index');
            $table->index('deadline', 'tasks_deadline_index');
            $table->index(['title', 'description'], 'tasks_search_index');
            
            // Восстанавливаем индексы на completed_at и category
            $table->index(['user_id', 'completed_at'], 'idx_user_completed');
            $table->index(['user_id', 'category'], 'idx_user_category');
            $table->index(['user_id', 'status', 'completed_at'], 'idx_user_status_completed');
        });
    }
};
