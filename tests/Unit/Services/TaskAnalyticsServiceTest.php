<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Services\Analytics\TaskAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TaskAnalyticsServiceTest extends TestCase
{
    private TaskAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = app(TaskAnalyticsService::class);
        Cache::flush(); // Очищаем кэш перед каждым тестом
    }

    public function test_get_task_creation_stats_for_day_period(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи в разные дни
        Task::factory()->forUser($user)->create(['created_at' => now()->subDays(1)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subDays(1)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subDays(2)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subDays(10)]); // Не должна попасть в выборку

        $stats = $this->analyticsService->getTaskCreationStats($user->id, 'day');

        $this->assertIsArray($stats);
        $this->assertCount(2, $stats); // 2 дня с задачами в пределах 7 дней
        
        // Проверяем структуру данных
        foreach ($stats as $stat) {
            $this->assertArrayHasKey('period', $stat);
            $this->assertArrayHasKey('count', $stat);
        }
    }

    public function test_get_task_creation_stats_for_week_period(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи в разные недели
        Task::factory()->forUser($user)->create(['created_at' => now()->subWeeks(1)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subWeeks(2)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subWeeks(2)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subWeeks(10)]); // Не должна попасть

        $stats = $this->analyticsService->getTaskCreationStats($user->id, 'week');

        $this->assertIsArray($stats);
        $this->assertGreaterThanOrEqual(1, count($stats));
        
        foreach ($stats as $stat) {
            $this->assertArrayHasKey('period', $stat);
            $this->assertArrayHasKey('count', $stat);
        }
    }

    public function test_get_task_creation_stats_for_month_period(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи в разные месяцы
        Task::factory()->forUser($user)->create(['created_at' => now()->subMonths(1)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subMonths(2)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subMonths(2)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subMonths(15)]); // Не должна попасть

        $stats = $this->analyticsService->getTaskCreationStats($user->id, 'month');

        $this->assertIsArray($stats);
        $this->assertGreaterThanOrEqual(1, count($stats));
        
        foreach ($stats as $stat) {
            $this->assertArrayHasKey('period', $stat);
            $this->assertArrayHasKey('count', $stat);
        }
    }

    public function test_get_completion_stats(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи с разными статусами
        Task::factory()->forUser($user)->completed()->count(3)->create();
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_TODO]);
        Task::factory()->forUser($user)->inProgress()->count(2)->create();

        $stats = $this->analyticsService->getCompletionStats($user->id);

        $this->assertIsArray($stats);
        $this->assertCount(2, $stats);
        
        $completedStat = collect($stats)->firstWhere('status', 'Выполненные');
        $notCompletedStat = collect($stats)->firstWhere('status', 'Невыполненные');
        
        $this->assertEquals(3, $completedStat['count']);
        $this->assertEquals(3, $notCompletedStat['count']); // 1 todo + 2 in_progress
    }

    public function test_get_priority_stats(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи с разными приоритетами
        Task::factory()->forUser($user)->highPriority()->count(2)->create();
        Task::factory()->forUser($user)->create(['priority' => Task::PRIORITY_MEDIUM]);
        Task::factory()->forUser($user)->lowPriority()->count(3)->create();

        $stats = $this->analyticsService->getPriorityStats($user->id);

        $this->assertIsArray($stats);
        $this->assertCount(3, $stats);
        
        $highPriorityStat = collect($stats)->firstWhere('priority', 'Высокий');
        $mediumPriorityStat = collect($stats)->firstWhere('priority', 'Средний');
        $lowPriorityStat = collect($stats)->firstWhere('priority', 'Низкий');
        
        $this->assertEquals(2, $highPriorityStat['count']);
        $this->assertEquals(1, $mediumPriorityStat['count']);
        $this->assertEquals(3, $lowPriorityStat['count']);
    }

    public function test_get_weekly_activity_stats(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи в разные дни недели
        $monday = now()->startOfWeek(); // Понедельник
        $tuesday = now()->startOfWeek()->addDay(); // Вторник
        
        Task::factory()->forUser($user)->create(['created_at' => $monday]);
        Task::factory()->forUser($user)->create(['created_at' => $monday]);
        Task::factory()->forUser($user)->create(['created_at' => $tuesday]);

        $stats = $this->analyticsService->getWeeklyActivityStats($user->id);

        $this->assertIsArray($stats);
        $this->assertGreaterThanOrEqual(1, count($stats));
        
        foreach ($stats as $stat) {
            $this->assertArrayHasKey('day', $stat);
            $this->assertArrayHasKey('count', $stat);
            $this->assertContains($stat['day'], [
                'Понедельник', 'Вторник', 'Среда', 'Четверг', 
                'Пятница', 'Суббота', 'Воскресенье'
            ]);
        }
    }

    public function test_get_overall_stats(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи с разными статусами
        Task::factory()->forUser($user)->completed()->count(5)->create();
        Task::factory()->forUser($user)->inProgress()->count(2)->create();
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_TODO]);
        
        // Создаем выполненные задачи за последние 30 дней
        Task::factory()->forUser($user)->create([
            'status' => Task::STATUS_DONE,
            'updated_at' => now()->subDays(15)
        ]);
        Task::factory()->forUser($user)->create([
            'status' => Task::STATUS_DONE,
            'updated_at' => now()->subDays(40) // Не должна попасть в статистику за 30 дней
        ]);

        $stats = $this->analyticsService->getOverallStats($user->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_tasks', $stats);
        $this->assertArrayHasKey('completed_tasks', $stats);
        $this->assertArrayHasKey('in_progress_tasks', $stats);
        $this->assertArrayHasKey('todo_tasks', $stats);
        $this->assertArrayHasKey('completion_rate', $stats);
        $this->assertArrayHasKey('completed_last_30_days', $stats);
        
        $this->assertEquals(10, $stats['total_tasks']); // 5 + 2 + 1 + 1 + 1 = 10 задач (включая задачу за 40 дней)
        $this->assertEquals(7, $stats['completed_tasks']); // 5 + 1 + 1 = 7 (включая задачу за 40 дней)
        $this->assertEquals(2, $stats['in_progress_tasks']);
        $this->assertEquals(1, $stats['todo_tasks']);
        $this->assertEquals(70.0, $stats['completion_rate']); // 7/10 * 100 = 70%
        $this->assertEquals(6, $stats['completed_last_30_days']); // 5 из factory + 1 за 15 дней
    }

    public function test_analytics_caching_functionality(): void
    {
        $user = User::factory()->create();
        
        // Отключаем observer для чистого теста кэширования
        Task::unsetEventDispatcher();
        
        // Очищаем кэш перед тестом
        $this->analyticsService->clearUserAnalyticsCache($user->id);
        
        Task::factory()->forUser($user)->completed()->create();

        // Первый вызов - данные должны быть закэшированы
        $stats1 = $this->analyticsService->getCompletionStats($user->id);
        
        // Создаем новую задачу, но кэш не должен обновиться автоматически
        Task::factory()->forUser($user)->create(['status' => Task::STATUS_TODO]);
        
        // Второй вызов - должен вернуть закэшированные данные
        $stats2 = $this->analyticsService->getCompletionStats($user->id);
        
        $this->assertEquals($stats1, $stats2);
        
        // Очищаем кэш и проверяем, что данные обновились
        $this->analyticsService->clearUserAnalyticsCache($user->id);
        $stats3 = $this->analyticsService->getCompletionStats($user->id);
        
        $this->assertNotEquals($stats1, $stats3);
    }

    public function test_clear_user_analytics_cache(): void
    {
        $user = User::factory()->create();
        Task::factory()->forUser($user)->create();

        // Заполняем кэш
        $this->analyticsService->getCompletionStats($user->id);
        $this->analyticsService->getPriorityStats($user->id);
        $this->analyticsService->getOverallStats($user->id);

        // Проверяем, что кэш заполнен, используя CacheService
        $cacheService = app(\App\Services\Cache\CacheService::class);
        $keyGenerator = app(\App\Services\Cache\CacheKeyGenerator::class);
        
        $completionKey = $keyGenerator->analytics($user->id, 'completion');
        $prioritiesKey = $keyGenerator->analytics($user->id, 'priorities');
        $overallKey = $keyGenerator->analytics($user->id, 'overall');
        
        // Для array драйвера проверяем без тегов
        $tags = config('cache.default') === 'array' ? [] : $cacheService->getAnalyticsTags($user->id);
        
        $this->assertTrue($cacheService->has($completionKey, $tags));
        $this->assertTrue($cacheService->has($prioritiesKey, $tags));
        $this->assertTrue($cacheService->has($overallKey, $tags));

        // Очищаем кэш
        $this->analyticsService->clearUserAnalyticsCache($user->id);

        // Проверяем, что кэш очищен
        $this->assertFalse($cacheService->has($completionKey, $tags));
        $this->assertFalse($cacheService->has($prioritiesKey, $tags));
        $this->assertFalse($cacheService->has($overallKey, $tags));
    }

    public function test_analytics_with_empty_dataset(): void
    {
        $user = User::factory()->create();
        // Не создаем никаких задач

        $completionStats = $this->analyticsService->getCompletionStats($user->id);
        $priorityStats = $this->analyticsService->getPriorityStats($user->id);
        $overallStats = $this->analyticsService->getOverallStats($user->id);
        $creationStats = $this->analyticsService->getTaskCreationStats($user->id, 'day');

        // Проверяем, что методы не падают с пустыми данными
        $this->assertIsArray($completionStats);
        $this->assertIsArray($priorityStats);
        $this->assertIsArray($overallStats);
        $this->assertIsArray($creationStats);
        
        // Проверяем корректные значения для пустого набора
        $this->assertEquals(0, $overallStats['total_tasks']);
        $this->assertEquals(0, $overallStats['completion_rate']);
    }

    public function test_analytics_filters_by_user_correctly(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Создаем задачи для разных пользователей
        Task::factory()->forUser($user1)->completed()->count(3)->create();
        Task::factory()->forUser($user2)->completed()->count(5)->create();

        $user1Stats = $this->analyticsService->getCompletionStats($user1->id);
        $user2Stats = $this->analyticsService->getCompletionStats($user2->id);

        $user1Completed = collect($user1Stats)->firstWhere('status', 'Выполненные')['count'];
        $user2Completed = collect($user2Stats)->firstWhere('status', 'Выполненные')['count'];

        $this->assertEquals(3, $user1Completed);
        $this->assertEquals(5, $user2Completed);
    }

    public function test_analytics_handles_different_time_periods(): void
    {
        $user = User::factory()->create();
        
        // Создаем задачи в разное время
        Task::factory()->forUser($user)->create(['created_at' => now()->subDays(1)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subWeeks(1)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subMonths(1)]);
        Task::factory()->forUser($user)->create(['created_at' => now()->subYears(1)]);

        $dayStats = $this->analyticsService->getTaskCreationStats($user->id, 'day');
        $weekStats = $this->analyticsService->getTaskCreationStats($user->id, 'week');
        $monthStats = $this->analyticsService->getTaskCreationStats($user->id, 'month');

        // Каждый период должен включать разное количество задач
        $this->assertIsArray($dayStats);
        $this->assertIsArray($weekStats);
        $this->assertIsArray($monthStats);
        
        // День должен включать меньше задач, чем неделя, а неделя меньше чем месяц
        $dayTotal = array_sum(array_column($dayStats, 'count'));
        $weekTotal = array_sum(array_column($weekStats, 'count'));
        $monthTotal = array_sum(array_column($monthStats, 'count'));
        
        $this->assertLessThanOrEqual($weekTotal, $dayTotal);
        $this->assertLessThanOrEqual($monthTotal, $weekTotal);
    }
}