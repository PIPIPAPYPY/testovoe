<?php

namespace Tests\Helpers;

use App\Models\Task;
use App\Models\User;
use Tests\Seeders\TestDataSeeder;
use Carbon\Carbon;

/**
 * Вспомогательный класс для создания тестовых данных
 */
class TestDataHelper
{
    /**
     * Создать пользователя с задачами для тестирования аналитики
     */
    public static function createUserWithAnalyticsData(): User
    {
        $user = User::factory()->create();
        $seeder = new TestDataSeeder();
        $seeder->createAnalyticsTestData($user);

        return $user;
    }

    /**
     * Создать набор задач с разными статусами
     */
    public static function createTasksWithStatuses(User $user, array $statusCounts): array
    {
        $tasks = [];

        foreach ($statusCounts as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $tasks[] = Task::factory()->forUser($user)->create([
                    'status' => $status,
                    'created_at' => now()->subDays(rand(1, 30))
                ]);
            }
        }

        return $tasks;
    }

    /**
     * Создать задачи с разными приоритетами
     */
    public static function createTasksWithPriorities(User $user, array $priorityCounts): array
    {
        $tasks = [];

        foreach ($priorityCounts as $priority => $count) {
            for ($i = 0; $i < $count; $i++) {
                $tasks[] = Task::factory()->forUser($user)->create([
                    'priority' => $priority,
                    'created_at' => now()->subDays(rand(1, 30))
                ]);
            }
        }

        return $tasks;
    }

    /**
     * Создать просроченные задачи
     */
    public static function createOverdueTasks(User $user, int $count): array
    {
        $tasks = [];

        for ($i = 0; $i < $count; $i++) {
            $tasks[] = Task::factory()->forUser($user)->create([
                'deadline' => now()->subDays(rand(1, 30)),
                'status' => rand(0, 1) ? Task::STATUS_TODO : Task::STATUS_IN_PROGRESS,
                'created_at' => now()->subDays(rand(31, 60))
            ]);
        }

        return $tasks;
    }

    /**
     * Создать задачи для тестирования еженедельной активности
     */
    public static function createWeeklyActivityTasks(User $user): array
    {
        $tasks = [];
        $daysOfWeek = [0, 1, 2, 3, 4, 5, 6]; // Воскресенье = 0, Понедельник = 1, и т.д.

        foreach ($daysOfWeek as $dayOfWeek) {
            $date = now()->startOfWeek()->addDays($dayOfWeek);
            
            // Создаем разное количество задач для каждого дня недели
            $tasksCount = ($dayOfWeek == 0 || $dayOfWeek == 6) ? rand(0, 2) : rand(1, 4);
            
            for ($i = 0; $i < $tasksCount; $i++) {
                $tasks[] = Task::factory()->forUser($user)->create([
                    'created_at' => $date->copy()->addHours(rand(8, 18)),
                    'status' => self::getRandomStatus(),
                    'priority' => self::getRandomPriority()
                ]);
            }
        }

        return $tasks;
    }

    /**
     * Создать задачи для тестирования временных периодов
     */
    public static function createTimeBasedTasks(User $user): array
    {
        $tasks = [];

        // Задачи за последние 7 дней
        for ($i = 1; $i <= 7; $i++) {
            $tasks[] = Task::factory()->forUser($user)->create([
                'created_at' => now()->subDays($i),
                'title' => "Daily Task {$i}"
            ]);
        }

        // Задачи за последние 8 недель
        for ($i = 1; $i <= 8; $i++) {
            $tasks[] = Task::factory()->forUser($user)->create([
                'created_at' => now()->subWeeks($i),
                'title' => "Weekly Task {$i}"
            ]);
        }

        // Задачи за последние 12 месяцев
        for ($i = 1; $i <= 12; $i++) {
            $tasks[] = Task::factory()->forUser($user)->create([
                'created_at' => now()->subMonths($i),
                'title' => "Monthly Task {$i}"
            ]);
        }

        return $tasks;
    }

    /**
     * Создать задачи с поисковыми данными
     */
    public static function createSearchableTasks(User $user): array
    {
        return [
            Task::factory()->forUser($user)->create([
                'title' => 'Important Meeting with Client',
                'description' => 'Discuss project requirements'
            ]),
            Task::factory()->forUser($user)->create([
                'title' => 'Buy Groceries',
                'description' => 'Milk, bread, eggs'
            ]),
            Task::factory()->forUser($user)->create([
                'title' => 'Code Review',
                'description' => 'Review pull request for authentication feature'
            ]),
            Task::factory()->forUser($user)->create([
                'title' => 'Team Meeting',
                'description' => 'Weekly standup meeting'
            ]),
            Task::factory()->forUser($user)->create([
                'title' => 'Documentation Update',
                'description' => 'Update API documentation'
            ])
        ];
    }

    /**
     * Получить случайный статус задачи
     */
    private static function getRandomStatus(): string
    {
        $statuses = [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_DONE];
        return $statuses[array_rand($statuses)];
    }

    /**
     * Получить случайный приоритет задачи
     */
    private static function getRandomPriority(): int
    {
        $priorities = [Task::PRIORITY_HIGH, Task::PRIORITY_MEDIUM, Task::PRIORITY_LOW];
        return $priorities[array_rand($priorities)];
    }

    /**
     * Создать валидные данные для создания задачи
     */
    public static function getValidTaskData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => Task::PRIORITY_MEDIUM,
            'status' => Task::STATUS_TODO,
            'deadline' => now()->addDays(7)->format('Y-m-d H:i:s')
        ], $overrides);
    }

    /**
     * Создать невалидные данные для тестирования валидации
     */
    public static function getInvalidTaskData(): array
    {
        return [
            'empty_title' => [
                'description' => 'Task without title'
            ],
            'long_title' => [
                'title' => str_repeat('a', 256), // Превышает лимит в 255 символов
                'description' => 'Task with too long title'
            ],
            'invalid_priority' => [
                'title' => 'Test Task',
                'priority' => 5 // Недопустимый приоритет
            ],
            'invalid_status' => [
                'title' => 'Test Task',
                'status' => 'invalid_status'
            ],
            'past_deadline' => [
                'title' => 'Test Task',
                'deadline' => now()->subDays(1)->format('Y-m-d H:i:s')
            ],
            'long_description' => [
                'title' => 'Test Task',
                'description' => str_repeat('a', 1001) // Превышает лимит в 1000 символов
            ]
        ];
    }

    /**
     * Создать данные для тестирования фильтров
     */
    public static function createFilterTestData(User $user): array
    {
        return [
            'high_priority_todo' => Task::factory()->forUser($user)->create([
                'priority' => Task::PRIORITY_HIGH,
                'status' => Task::STATUS_TODO,
                'created_at' => now()->subDays(1)
            ]),
            'medium_priority_in_progress' => Task::factory()->forUser($user)->create([
                'priority' => Task::PRIORITY_MEDIUM,
                'status' => Task::STATUS_IN_PROGRESS,
                'created_at' => now()->subDays(2)
            ]),
            'low_priority_done' => Task::factory()->forUser($user)->create([
                'priority' => Task::PRIORITY_LOW,
                'status' => Task::STATUS_DONE,
                'created_at' => now()->subDays(3)
            ]),
            'overdue_task' => Task::factory()->forUser($user)->create([
                'deadline' => now()->subDays(5),
                'status' => Task::STATUS_TODO,
                'created_at' => now()->subDays(10)
            ])
        ];
    }

    /**
     * Использовать TestDataSeeder для создания полного набора тестовых данных
     */
    public static function seedTestData(): void
    {
        $seeder = new TestDataSeeder();
        $seeder->run();
    }

    /**
     * Создать данные для тестирования производительности
     */
    public static function createPerformanceTestData(User $user, int $count = 1000): void
    {
        $seeder = new TestDataSeeder();
        $seeder->createPerformanceTestData($user, $count);
    }

    /**
     * Очистить все тестовые данные
     */
    public static function clearTestData(): void
    {
        $seeder = new TestDataSeeder();
        $seeder->clearTestData();
    }
}