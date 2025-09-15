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
        //
    }

    /**
     * Выполнить начальную настройку приложения
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
