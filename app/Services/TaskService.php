<?php

namespace App\Services;

use App\Models\Task;
use App\Services\Analytics\AnalyticsServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для работы с задачами
 * 
 * Реализует бизнес-логику управления задачами
 * Следует принципам SOLID и DRY
 */
class TaskService
{
    private const CACHE_TTL = 300; // 5 минут
    
    public function __construct(
        private AnalyticsServiceInterface $analyticsService
    ) {}

    /**
     * Получить задачи пользователя с фильтрацией и пагинацией
     */
    public function getUserTasks(
        int $userId, 
        array $filters = [], 
        int $perPage = 12
    ): LengthAwarePaginator {
        $query = $this->buildTaskQuery($userId, $filters);
        
        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage)
                    ->appends($filters);
    }

    /**
     * Создать новую задачу
     */
    public function createTask(int $userId, array $data): Task
    {
        $task = DB::transaction(function () use ($userId, $data) {
            $task = Task::create([
                'user_id' => $userId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 2,
                'status' => $data['status'] ?? 'todo',
                'deadline' => $data['deadline'] ?? null,
            ]);

            $this->clearUserCache($userId);
            
            return $task;
        });

        return $task;
    }

    /**
     * Обновить задачу
     */
    public function updateTask(Task $task, array $data): Task
    {
        DB::transaction(function () use ($task, $data) {
            $task->update($data);
            $this->clearUserCache($task->user_id);
        });

        return $task->fresh();
    }

    /**
     * Удалить задачу
     */
    public function deleteTask(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            $userId = $task->user_id;
            $result = $task->delete();
            $this->clearUserCache($userId);
            
            return $result;
        });
    }

    /**
     * Получить статистику по статусам с кэшированием
     */
    public function getStatusCounts(int $userId): array
    {
        $cacheKey = "user_{$userId}_task_counts";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $counts = Task::forUser($userId)
                ->selectRaw('
                    COUNT(*) as all_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as todo_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as done_count
                ', ['todo', 'in_progress', 'done'])
                ->first();
            
            return [
                'all' => $counts->all_count,
                'todo' => $counts->todo_count,
                'in_progress' => $counts->in_progress_count,
                'done' => $counts->done_count,
            ];
        });
    }

    /**
     * Построить запрос для задач с фильтрами
     */
    private function buildTaskQuery(int $userId, array $filters): Builder
    {
        $query = Task::forUser($userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query;
    }

    /**
     * Применить поиск к запросу
     */
    private function applySearchFilter(Builder $query, string $search): void
    {
        $searchTerm = trim($search);
        if (empty($searchTerm)) {
            return;
        }

        $searchTerm = preg_replace('/[\x00-\x1F\x7F]/u', '', $searchTerm);
        if (empty($searchTerm)) {
            return;
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);
        $words = preg_split('/\s+/u', $escaped, -1, PREG_SPLIT_NO_EMPTY);
        
        if (empty($words)) {
            return;
        }

        $query->where(function ($q) use ($words) {
            foreach ($words as $word) {
                $like = '%' . mb_strtolower($word) . '%';
                $q->where(function ($q2) use ($like) {
                    $q2->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$like]);
                });
            }
        });
    }

    /**
     * Очистить кэш пользователя
     */
    private function clearUserCache(int $userId): void
    {
        Cache::forget("user_{$userId}_task_counts");
        $this->analyticsService->clearUserAnalyticsCache($userId);
    }
}