<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Основной сервис-провайдер приложения
 * 
 * Регистрирует сервисы и выполняет начальную настройку приложения
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Зарегистрировать сервисы приложения
     * @return void
     */
    public function register(): void
    {
        // Регистрируем TaskService как singleton
        $this->app->singleton(\App\Services\TaskService::class);
    }

    /**
     * Выполнить начальную настройку приложения
     * @return void
     */
    public function boot(): void
    {
        // Регистрируем Observer для модели Task
        \App\Models\Task::observe(\App\Observers\TaskObserver::class);
    }
}
