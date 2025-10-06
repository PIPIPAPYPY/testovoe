<?php

namespace Tests\Feature\Seeders;

use Tests\TestCase;
use Tests\Seeders\TestDataSeeder;
use App\Models\User;
use App\Models\Task;

class TestDataSeederTest extends TestCase
{
    public function test_seeder_creates_test_users_and_tasks(): void
    {
        // Получаем начальное количество записей
        $initialUserCount = User::count();
        $initialTaskCount = Task::count();

        // Запускаем seeder
        $seeder = new TestDataSeeder();
        $seeder->run();

        // Проверяем, что пользователи созданы
        $this->assertEquals($initialUserCount + 4, User::count()); // 1 основной + 3 дополнительных

        // Проверяем основного тестового пользователя
        $mainUser = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($mainUser);
        $this->assertEquals('Test User', $mainUser->name);

        // Проверяем, что задачи созданы для каждого пользователя
        $this->assertGreaterThan(0, Task::count());
        
        // Каждый пользователь должен иметь 6 задач (по дизайну seeder'а)
        // Плюс дополнительные задачи из createAnalyticsTestData и createPerformanceTestData
        $this->assertGreaterThanOrEqual(24, Task::count()); // Минимум 4 пользователя * 6 задач
    }

    public function test_seeder_creates_analytics_test_data(): void
    {
        $user = User::factory()->create();
        $seeder = new TestDataSeeder();

        // Создаем данные для аналитики
        $seeder->createAnalyticsTestData($user);

        // Проверяем, что созданы задачи за последние 30 дней
        $recentTasks = Task::forUser($user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $this->assertGreaterThan(30, $recentTasks); // Минимум 1 задача в день

        // Проверяем, что есть задачи для разных дней недели
        $weeklyTasks = Task::forUser($user->id)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        $this->assertGreaterThan(0, $weeklyTasks);
    }

    public function test_seeder_creates_performance_test_data(): void
    {
        $user = User::factory()->create();
        $seeder = new TestDataSeeder();

        // Создаем данные для тестирования производительности
        $seeder->createPerformanceTestData($user, 100);

        // Проверяем, что создано правильное количество задач
        $this->assertEquals(100, Task::forUser($user->id)->count());

        // Проверяем, что задачи имеют разные статусы и приоритеты
        $statuses = Task::forUser($user->id)->distinct('status')->pluck('status')->toArray();
        $priorities = Task::forUser($user->id)->distinct('priority')->pluck('priority')->toArray();

        $this->assertGreaterThan(1, count($statuses));
        $this->assertGreaterThan(1, count($priorities));
    }

    public function test_seeder_clear_test_data(): void
    {
        // Создаем тестовые данные
        User::factory()->count(3)->create();
        Task::factory()->count(10)->create();

        $this->assertGreaterThan(0, User::count());
        $this->assertGreaterThan(0, Task::count());

        // Очищаем данные
        $seeder = new TestDataSeeder();
        $seeder->clearTestData();

        // Проверяем, что данные очищены
        $this->assertEquals(0, User::count());
        $this->assertEquals(0, Task::count());
    }

    public function test_seeder_only_runs_in_testing_environment(): void
    {
        // Временно меняем окружение
        app()->detectEnvironment(function () {
            return 'production';
        });

        $initialUserCount = User::count();
        $initialTaskCount = Task::count();

        // Пытаемся запустить seeder
        $seeder = new TestDataSeeder();
        $seeder->run();

        // Проверяем, что данные не были созданы
        $this->assertEquals($initialUserCount, User::count());
        $this->assertEquals($initialTaskCount, Task::count());

        // Возвращаем тестовое окружение
        app()->detectEnvironment(function () {
            return 'testing';
        });
    }
}