<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\Analytics\AnalyticsServiceInterface;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer для модели Task
 * 
 * Реализует паттерн Observer для отслеживания изменений в задачах
 * и автоматического обновления связанных данных
 */
class TaskObserver
{
    public function __construct(
        private CacheService $cacheService
    ) {}
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
    }

    /**
     * Обработка после обновления задачи
     */
    public function updated(Task $task): void
    {
        if ($task->user_id) {
            $this->clearUserCaches($task->user_id);
        }
        
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
        
        try {
            $userTags = $this->cacheService->getUserTags($userId);
            $this->cacheService->flushTags($userTags);
            
            $analyticsTags = $this->cacheService->getAnalyticsTags($userId);
            $this->cacheService->flushTags($analyticsTags);
            
            $apiTags = $this->cacheService->getApiTags('*', $userId);
            $this->cacheService->flushTags($apiTags);
            
        } catch (\Exception $e) {
            Log::warning('Failed to clear user cache', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
}