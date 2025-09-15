<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Сидер для создания тестовых задач
 * 
 * Заполняет базу данных примерами задач для тестирования
 */
class TaskSeeder extends Seeder
{
    /**
     * Выполнить заполнение базы данных тестовыми задачами
     * @return void
     */
    public function run(): void
    {
        Task::truncate();

        $tasks = [
            [
                'title' => 'Изучить Laravel',
                'description' => 'Изучить основы фреймворка Laravel, включая маршрутизацию, контроллеры и модели',
                'status' => 'todo'
            ],
            [
                'title' => 'Создать API',
                'description' => 'Разработать REST API для управления задачами с CRUD операциями',
                'status' => 'done'
            ],
            [
                'title' => 'Написать тесты',
                'description' => 'Создать unit и feature тесты для проверки функциональности приложения',
                'status' => 'todo'
            ],
            [
                'title' => 'Деплой приложения',
                'description' => 'Развернуть приложение на продакшн сервере и настроить CI/CD',
                'status' => 'todo'
            ],
            [
                'title' => 'Оптимизация базы данных',
                'description' => 'Проанализировать и оптимизировать запросы к базе данных',
                'status' => 'in_progress'
            ],
            [
                'title' => 'Документация API',
                'description' => 'Создать подробную документацию для API с примерами использования',
                'status' => 'todo'
            ],
            [
                'title' => 'Интеграция с внешними сервисами',
                'description' => 'Интегрировать приложение с внешними API (платежи, уведомления)',
                'status' => 'done'
            ],
            [
                'title' => 'Мониторинг и логирование',
                'description' => 'Настроить систему мониторинга и логирования для отслеживания ошибок',
                'status' => 'todo'
            ]
        ];

        foreach ($tasks as $taskData) {
            Task::create($taskData);
        }

        $this->command->info('Тестовые задачи созданы успешно!');
    }
}
