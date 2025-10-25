<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;

/**
 * Сидер для создания тестовых данных аналитики задач
 */
class TaskAnalyticsSeeder extends Seeder
{
    /**
     * Запустить сидер
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->error('Пользователь не найден. Сначала создайте пользователя.');
            return;
        }

        $this->command->info("Создаем тестовые данные для пользователя: {$user->name}");

        $tasks = [
            [
                'title' => ' Критическая ошибка в продакшене',
                'description' => 'Необходимо срочно исправить ошибку, которая влияет на всех пользователей',
                'status' => 'done',
                'priority' => 1,
                'created_at' => now()->subDays(5)->setHour(9),
                'updated_at' => now()->subDays(4)->setHour(15),
            ],
            [
                'title' => ' Оптимизация базы данных',
                'description' => 'Улучшить производительность критических запросов',
                'status' => 'in_progress',
                'priority' => 1,
                'created_at' => now()->subDays(3)->setHour(10),
                'updated_at' => now()->subDays(2)->setHour(16),
            ],
            [
                'title' => ' Обновление системы безопасности',
                'description' => 'Внедрить новые меры безопасности',
                'status' => 'todo',
                'priority' => 1,
                'created_at' => now()->subDays(1)->setHour(14),
                'updated_at' => now()->subDays(1)->setHour(14),
            ],

            [
                'title' => ' Создание отчетов аналитики',
                'description' => 'Разработать систему отчетов для менеджмента',
                'status' => 'done',
                'priority' => 2,
                'created_at' => now()->subDays(7)->setHour(11),
                'updated_at' => now()->subDays(6)->setHour(17),
            ],
            [
                'title' => ' Обновление дизайна интерфейса',
                'description' => 'Улучшить пользовательский интерфейс приложения',
                'status' => 'in_progress',
                'priority' => 2,
                'created_at' => now()->subDays(4)->setHour(13),
                'updated_at' => now()->subDays(3)->setHour(18),
            ],
            [
                'title' => ' Адаптация под мобильные устройства',
                'description' => 'Сделать интерфейс адаптивным для смартфонов',
                'status' => 'todo',
                'priority' => 2,
                'created_at' => now()->subDays(2)->setHour(15),
                'updated_at' => now()->subDays(2)->setHour(15),
            ],

            [
                'title' => ' Обновление документации',
                'description' => 'Привести в порядок техническую документацию',
                'status' => 'done',
                'priority' => 3,
                'created_at' => now()->subDays(10)->setHour(16),
                'updated_at' => now()->subDays(9)->setHour(19),
            ],
            [
                'title' => ' Рефакторинг старого кода',
                'description' => 'Улучшить читаемость и структуру кода',
                'status' => 'todo',
                'priority' => 3,
                'created_at' => now()->subDays(6)->setHour(12),
                'updated_at' => now()->subDays(6)->setHour(12),
            ],
            [
                'title' => ' Настройка мониторинга',
                'description' => 'Внедрить систему мониторинга производительности',
                'status' => 'todo',
                'priority' => 3,
                'created_at' => now()->subHours(8)->setHour(20),
                'updated_at' => now()->subHours(8)->setHour(20),
            ],

            [
                'title' => ' Настройка CI/CD пайплайна',
                'description' => 'Автоматизировать процесс развертывания',
                'status' => 'done',
                'priority' => 2,
                'created_at' => now()->subDays(8)->setHour(8),
                'updated_at' => now()->subDays(7)->setHour(12),
            ],
            [
                'title' => ' Анализ метрик производительности',
                'description' => 'Изучить показатели работы системы',
                'status' => 'in_progress',
                'priority' => 2,
                'created_at' => now()->subHours(12)->setHour(21),
                'updated_at' => now()->subHours(6)->setHour(22),
            ],
            [
                'title' => ' Добавление новых функций',
                'description' => 'Реализовать запрошенные пользователями возможности',
                'status' => 'todo',
                'priority' => 1,
                'created_at' => now()->subHours(4)->setHour(7),
                'updated_at' => now()->subHours(4)->setHour(7),
            ],
        ];

        foreach ($tasks as $taskData) {
            $existing = Task::where('title', $taskData['title'])
                          ->where('user_id', $user->id)
                          ->first();

            if (!$existing) {
                Task::create(array_merge($taskData, [
                    'user_id' => $user->id,
                    'deadline' => rand(0, 1) ? now()->addDays(rand(1, 30)) : null,
                ]));

                $this->command->info(" Создана задача: {$taskData['title']}");
            } else {
                $this->command->warn("  Задача уже существует: {$taskData['title']}");
            }
        }

        $this->command->info(' Тестовые данные для аналитики созданы успешно!');
    }
}
