<?php

namespace App\Services\Analytics;

use App\Models\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Сервис аналитики задач
 * 
 * Реализует интерфейс AnalyticsServiceInterface для получения
 * различных видов аналитических данных по задачам пользователя
 */
class TaskAnalyticsService implements AnalyticsServiceInterface
{
    private const CACHE_TTL = 300; // 5 минут

    /**
     * Получить статистику создания задач за период
     */
    public function getTaskCreationStats(int $userId, string $period): array
    {
        $cacheKey = "analytics_creation_{$userId}_{$period}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $period) {
            $startDate = $this->getPeriodStart($period);
            $groupBy = $this->getGroupByFormat($period);
            
            return Task::forUser($userId)
                ->where('created_at', '>=', $startDate)
                ->select(
                    DB::raw("strftime('{$groupBy}', created_at) as period"),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->toArray();
        });
    }

    /**
     * Получить статистику выполненных vs невыполненных задач
     */
    public function getCompletionStats(int $userId): array
    {
        $cacheKey = "analytics_completion_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $completed = Task::forUser($userId)->where('status', 'done')->count();
            $notCompleted = Task::forUser($userId)->whereIn('status', ['todo', 'in_progress'])->count();
            
            return [
                ['status' => 'Выполненные', 'count' => $completed],
                ['status' => 'Невыполненные', 'count' => $notCompleted]
            ];
        });
    }

    /**
     * Получить статистику по приоритетам
     */
    public function getPriorityStats(int $userId): array
    {
        $cacheKey = "analytics_priorities_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $priorities = [
                1 => 'Высокий',
                2 => 'Средний', 
                3 => 'Низкий'
            ];

            $stats = Task::forUser($userId)
                ->select('priority', DB::raw('COUNT(*) as count'))
                ->groupBy('priority')
                ->orderBy('priority')
                ->get()
                ->toArray();

            // Преобразуем в читаемый формат
            $result = [];
            foreach ($stats as $stat) {
                $result[] = [
                    'priority' => $priorities[$stat['priority']] ?? 'Неизвестный',
                    'count' => $stat['count']
                ];
            }

            return $result;
        });
    }

    /**
     * Получить статистику активности по дням недели
     */
    public function getWeeklyActivityStats(int $userId): array
    {
        $cacheKey = "analytics_weekly_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $daysOfWeek = [
                1 => 'Понедельник',
                2 => 'Вторник',
                3 => 'Среда',
                4 => 'Четверг',
                5 => 'Пятница',
                6 => 'Суббота',
                0 => 'Воскресенье'
            ];

            $stats = Task::forUser($userId)
                ->selectRaw('strftime("%w", created_at) as day_of_week, COUNT(*) as count')
                ->groupByRaw('strftime("%w", created_at)')
                ->orderByRaw('strftime("%w", created_at)')
                ->get()
                ->toArray();

            // Преобразуем в читаемый формат
            $result = [];
            foreach ($stats as $stat) {
                $result[] = [
                    'day' => $daysOfWeek[$stat['day_of_week']] ?? 'Неизвестный',
                    'count' => $stat['count']
                ];
            }

            return $result;
        });
    }



    /**
     * Получить общую статистику пользователя
     */
    public function getOverallStats(int $userId): array
    {
        $cacheKey = "analytics_overall_{$userId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            $totalTasks = Task::forUser($userId)->count();
            $completedTasks = Task::forUser($userId)->where('status', 'done')->count();
            $inProgressTasks = Task::forUser($userId)->where('status', 'in_progress')->count();
            $todoTasks = Task::forUser($userId)->where('status', 'todo')->count();
            
            $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
            
            // Статистика за последние 30 дней - выполненные задачи
            $completedLast30Days = Task::forUser($userId)
                ->where('status', 'done')
                ->where('updated_at', '>=', now()->subDays(30))
                ->count();
                
            return [
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'in_progress_tasks' => $inProgressTasks,
                'todo_tasks' => $todoTasks,
                'completion_rate' => $completionRate,
                'completed_last_30_days' => $completedLast30Days,
            ];
        });
    }

    /**
     * Получить начальную дату для периода
     */
    private function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            'day' => now()->subDays(7), // Последние 7 дней
            'week' => now()->subWeeks(8), // Последние 8 недель
            'month' => now()->subMonths(12), // Последние 12 месяцев
            default => now()->subMonths(3), // По умолчанию 3 месяца
        };
    }

    /**
     * Получить формат группировки для SQL (SQLite)
     */
    private function getGroupByFormat(string $period): string
    {
        return match ($period) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%W',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };
    }

    /**
     * Очистить кэш аналитики для пользователя
     */
    public function clearUserAnalyticsCache(int $userId): void
    {
        $keys = [
            "analytics_creation_{$userId}",
            "analytics_completion_{$userId}",
            "analytics_priorities_{$userId}",
            "analytics_weekly_{$userId}",
            "analytics_overall_{$userId}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}