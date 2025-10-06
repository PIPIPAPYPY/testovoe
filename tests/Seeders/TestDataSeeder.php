<?php

namespace Tests\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Task;

/**
 * Seeder для создания тестовых данных
 * Используется только в тестовой среде
 */
class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Проверяем, что мы в тестовой среде
        if (!app()->environment('testing')) {
            return;
        }

        $this->createTestUsers();
        $this->createTestTasks();
    }

    /**
     * Создать тестовых пользователей
     */
    private function createTestUsers(): void
    {
        // Создаем основного тестового пользователя
        $mainUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        // Создаем дополнительных пользователей для тестирования изоляции данных
        User::factory()->count(3)->create();
    }

    /**
     * Создать тестовые задачи
     */
    private function createTestTasks(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Создаем задачи с разными статусами
            Task::factory()->forUser($user)->create([
                'title' => 'Completed Task',
                'status' => Task::STATUS_DONE,
                'priority' => Task::PRIORITY_HIGH,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(2)
            ]);

            Task::factory()->forUser($user)->create([
                'title' => 'In Progress Task',
                'status' => Task::STATUS_IN_PROGRESS,
                'priority' => Task::PRIORITY_MEDIUM,
                'created_at' => now()->subDays(3)
            ]);

            Task::factory()->forUser($user)->create([
                'title' => 'Todo Task',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_LOW,
                'deadline' => now()->addDays(7)
            ]);

            // Создаем просроченную задачу
            Task::factory()->forUser($user)->create([
                'title' => 'Overdue Task',
                'status' => Task::STATUS_TODO,
                'priority' => Task::PRIORITY_HIGH,
                'deadline' => now()->subDays(3),
                'created_at' => now()->subDays(10)
            ]);

            // Создаем задачи для тестирования поиска
            Task::factory()->forUser($user)->create([
                'title' => 'Important Meeting',
                'description' => 'Meeting with important client about project requirements'
            ]);

            Task::factory()->forUser($user)->create([
                'title' => 'Code Review',
                'description' => 'Review authentication feature implementation'
            ]);
        }
    }

    /**
     * Создать данные для тестирования аналитики
     */
    public function createAnalyticsTestData(User $user): void
    {
        // Создаем задачи за последние 30 дней для тестирования временной аналитики
        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays($i);
            
            // Создаем 1-3 задачи в день
            $tasksCount = rand(1, 3);
            
            for ($j = 0; $j < $tasksCount; $j++) {
                Task::factory()->forUser($user)->create([
                    'created_at' => $date->copy()->addHours(rand(8, 18)),
                    'status' => $this->getRandomStatus(),
                    'priority' => $this->getRandomPriority(),
                    'updated_at' => $date->copy()->addHours(rand(1, 12))
                ]);
            }
        }

        // Создаем задачи для разных дней недели
        $startOfWeek = now()->startOfWeek();
        for ($day = 0; $day < 7; $day++) {
            $date = $startOfWeek->copy()->addDays($day);
            
            // Больше задач в рабочие дни
            $tasksCount = ($day >= 1 && $day <= 5) ? rand(2, 5) : rand(0, 2);
            
            for ($i = 0; $i < $tasksCount; $i++) {
                Task::factory()->forUser($user)->create([
                    'created_at' => $date->copy()->addHours(rand(8, 18)),
                    'status' => $this->getRandomStatus(),
                    'priority' => $this->getRandomPriority()
                ]);
            }
        }
    }

    /**
     * Создать данные для тестирования производительности
     */
    public function createPerformanceTestData(User $user, int $count = 1000): void
    {
        $tasks = [];
        
        for ($i = 0; $i < $count; $i++) {
            $tasks[] = [
                'title' => "Performance Test Task {$i}",
                'description' => "Description for performance test task number {$i}",
                'status' => $this->getRandomStatus(),
                'priority' => $this->getRandomPriority(),
                'user_id' => $user->id,
                'deadline' => rand(0, 1) ? now()->addDays(rand(1, 30)) : null,
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now()->subDays(rand(0, 30))
            ];
        }

        // Используем chunk для вставки больших объемов данных
        collect($tasks)->chunk(100)->each(function ($chunk) {
            Task::insert($chunk->toArray());
        });
    }

    /**
     * Получить случайный статус
     */
    private function getRandomStatus(): string
    {
        $statuses = [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_DONE];
        return $statuses[array_rand($statuses)];
    }

    /**
     * Получить случайный приоритет
     */
    private function getRandomPriority(): int
    {
        $priorities = [Task::PRIORITY_HIGH, Task::PRIORITY_MEDIUM, Task::PRIORITY_LOW];
        return $priorities[array_rand($priorities)];
    }

    /**
     * Очистить тестовые данные
     */
    public function clearTestData(): void
    {
        Task::truncate();
        User::truncate();
    }
}