<?php

namespace App\Services\Analytics;

use App\Models\Task;
use App\Models\User;
use App\Services\Cache\CacheService;
use App\Services\Cache\CacheKeyGenerator;
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
    public function __construct(
        private CacheService $cacheService,
        private CacheKeyGenerator $keyGenerator
    ) {}

    /**
     * Получить статистику создания задач за период
     */
    public function getTaskCreationStats(int $userId, string $period): array
    {
        $cacheKey = $this->keyGenerator->analytics($userId, 'creation', $period);
        $tags = $this->cacheService->getAnalyticsTags($userId);
        
        return $this->cacheService->remember(
            $cacheKey,
            function () use ($userId, $period) {
                $startDate = $this->getPeriodStart($period);
                $groupBy = $this->getGroupByFormat($period);
                
                return Task::forUser($userId)
                    ->where('created_at', '>=', $startDate)
                    ->select(
                        DB::raw($this->formatDateColumn('created_at', $groupBy) . ' as period'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get()
                    ->toArray();
            },
            $this->cacheService->getTtl('analytics'),
            $tags
        );
    }

    /**
     * Получить статистику выполненных vs невыполненных задач
     */
    public function getCompletionStats(int $userId): array
    {
        $cacheKey = $this->keyGenerator->analytics($userId, 'completion');
        $tags = $this->cacheService->getAnalyticsTags($userId);
        
        return $this->cacheService->remember(
            $cacheKey,
            function () use ($userId) {
                $user = User::findOrFail($userId);
                $tasks = $user->tasks;
                
                $completed = $tasks->where('status', 'done')->count();
                $notCompleted = $tasks->whereIn('status', ['todo', 'in_progress'])->count();
                
                return [
                    ['status' => 'Выполненные', 'count' => $completed],
                    ['status' => 'Невыполненные', 'count' => $notCompleted]
                ];
            },
            $this->cacheService->getTtl('analytics'),
            $tags
        );
    }

    /**
     * Получить статистику по приоритетам
     */
    public function getPriorityStats(int $userId): array
    {
        $cacheKey = $this->keyGenerator->analytics($userId, 'priorities');
        $tags = $this->cacheService->getAnalyticsTags($userId);
        
        return $this->cacheService->remember(
            $cacheKey,
            function () use ($userId) {
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

                $result = [];
                foreach ($stats as $stat) {
                    $result[] = [
                        'priority' => $priorities[$stat['priority']] ?? 'Неизвестный',
                        'count' => $stat['count']
                    ];
                }

                return $result;
            },
            $this->cacheService->getTtl('analytics'),
            $tags
        );
    }

    /**
     * Получить статистику активности по дням недели
     */
    public function getWeeklyActivityStats(int $userId): array
    {
        $cacheKey = $this->keyGenerator->analytics($userId, 'weekly');
        $tags = $this->cacheService->getAnalyticsTags($userId);
        
        return $this->cacheService->remember(
            $cacheKey,
            function () use ($userId) {
                $dayFormat = $this->formatDateColumn('created_at', '%w');
                
                $stats = Task::forUser($userId)
                    ->selectRaw("{$dayFormat} as day_of_week, COUNT(*) as count")
                    ->groupByRaw($dayFormat)
                    ->orderByRaw($dayFormat)
                    ->get()
                    ->toArray();

                $result = [];
                foreach ($stats as $stat) {
                    $dayOfWeek = $this->convertDayOfWeek($stat['day_of_week']);
                    $result[] = [
                        'day' => $dayOfWeek,
                        'count' => $stat['count']
                    ];
                }

                return $result;
            },
            $this->cacheService->getTtl('analytics'),
            $tags
        );
    }



    /**
     * Получить общую статистику пользователя
     */
    public function getOverallStats(int $userId): array
    {
        $cacheKey = $this->keyGenerator->analytics($userId, 'overall');
        $tags = $this->cacheService->getAnalyticsTags($userId);
        
        return $this->cacheService->remember(
            $cacheKey,
            function () use ($userId) {
                $user = User::findOrFail($userId);
                $tasks = $user->tasks;
                
                $totalTasks = $tasks->count();
                $completedTasks = $tasks->where('status', 'done')->count();
                $inProgressTasks = $tasks->where('status', 'in_progress')->count();
                $todoTasks = $tasks->where('status', 'todo')->count();
                
                $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
                
                $completedLast30Days = $tasks
                    ->where('status', 'done')
                    ->filter(function ($task) {
                        return $task->updated_at && $task->updated_at->gte(now()->subDays(30));
                    })
                    ->count();
                    
                return [
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                    'in_progress_tasks' => $inProgressTasks,
                    'todo_tasks' => $todoTasks,
                    'completion_rate' => $completionRate,
                    'completed_last_30_days' => $completedLast30Days,
                ];
            },
            $this->cacheService->getTtl('analytics'),
            $tags
        );
    }

    /**
     * Получить формат даты для текущей базы данных
     */
    private function getDateFormat(string $format): string
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            return match ($format) {
                '%Y-%m-%d' => 'YYYY-MM-DD',
                '%Y-%W' => 'IYYY-IW',
                '%Y-%m' => 'YYYY-MM',
                '%w' => 'D',
                default => 'YYYY-MM-DD',
            };
        }
        
        return $format;
    }

    /**
     * Форматировать колонку даты для текущей базы данных
     */
    private function formatDateColumn(string $column, string $format): string
    {
        $driver = DB::connection()->getDriverName();
        $dateFormat = $this->getDateFormat($format);
        
        if ($driver === 'pgsql') {
            return "TO_CHAR({$column}, '{$dateFormat}')";
        }
        
        return "strftime('{$format}', {$column})";
    }

    /**
     * Конвертировать день недели в читаемый формат
     * Обрабатывает различия между SQLite (0-6, Sun-Sat) и PostgreSQL (1-7, Mon-Sun)
     */
    private function convertDayOfWeek(string $dayOfWeek): string
    {
        $driver = DB::connection()->getDriverName();
        
        $daysOfWeek = [
            'Понедельник',
            'Вторник', 
            'Среда',
            'Четверг',
            'Пятница',
            'Суббота',
            'Воскресенье'
        ];
        
        if ($driver === 'pgsql') {
            $index = (int)$dayOfWeek - 1;
            return $daysOfWeek[$index] ?? 'Неизвестный';
        } else {
            $sqliteMapping = [
                0 => 'Воскресенье',
                1 => 'Понедельник',
                2 => 'Вторник',
                3 => 'Среда',
                4 => 'Четверг',
                5 => 'Пятница',
                6 => 'Суббота'
            ];
            return $sqliteMapping[(int)$dayOfWeek] ?? 'Неизвестный';
        }
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
        $tags = $this->cacheService->getAnalyticsTags($userId);
        $this->cacheService->flushTags($tags);
    }

    /**
     * Прогреть кеш аналитики для пользователя
     */
    public function warmAnalyticsCache(int $userId): void
    {
        $this->getTaskCreationStats($userId, 'day');
        $this->getTaskCreationStats($userId, 'week');
        $this->getTaskCreationStats($userId, 'month');
        $this->getCompletionStats($userId);
        $this->getPriorityStats($userId);
        $this->getWeeklyActivityStats($userId);
        $this->getOverallStats($userId);
    }
}