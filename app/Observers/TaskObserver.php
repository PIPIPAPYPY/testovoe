<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\Analytics\AnalyticsServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Observer для модели Task
 * 
 * Реализует паттерн Observer для отслеживания изменений в задачах
 * и автоматического обновления связанных данных
 */
class TaskObserver
{
    /**
     * Обработка создания новой задачи
     */
    public function created(Task $task): void
    {
        if ($task->user_id) {
            $this->clearUserCaches($task->user_id);
        }
    }

    /**
     * Обработка обновления задачи
     */
    public function updating(Task $task): void
    {
        // Базовая логика обновления задач
    }

    /**
     * Обработка после обновления задачи
     */
    public function updated(Task $task): void
    {
        if ($task->user_id) {
            $this->clearUserCaches($task->user_id);
        }
        
        // Если изменился пользователь, очищаем кэш и для старого пользователя
        if ($task->wasChanged('user_id')) {
            $originalUserId = $task->getOriginal('user_id');
            if ($originalUserId) {
                $this->clearUserCaches($originalUserId);
            }
        }
    }

    /**
     * Обработка удаления задачи
     */
    public function deleted(Task $task): void
    {
        if ($task->user_id) {
            $this->clearUserCaches($task->user_id);
        }
    }

    /**
     * Очистить все кэши пользователя
     */
    private function clearUserCaches(?int $userId): void
    {
        if (!$userId) {
            return;
        }
        
        // Очищаем кэш статистики задач
        Cache::forget("user_{$userId}_task_counts");
        
        // Очищаем кэш аналитики
        $analyticsKeys = [
            "analytics_creation_{$userId}",
            "analytics_completion_{$userId}",
            "analytics_priorities_{$userId}",
            "analytics_weekly_{$userId}",
            "analytics_timeofday_{$userId}",
            "analytics_overall_{$userId}",
        ];

        foreach ($analyticsKeys as $key) {
            Cache::forget($key);
        }
    }
}