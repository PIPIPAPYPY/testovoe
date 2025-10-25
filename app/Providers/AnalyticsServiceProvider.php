<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Analytics\AnalyticsServiceInterface;
use App\Services\Analytics\TaskAnalyticsService;

/**
 * Service Provider для регистрации сервисов аналитики
 * 
 * Регистрирует зависимости для системы аналитики задач
 */
class AnalyticsServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов в контейнере
     */
    public function register(): void
    {
        $this->app->bind(AnalyticsServiceInterface::class, TaskAnalyticsService::class);
    }

    /**
     * Загрузка сервисов после регистрации
     */
    public function boot(): void
    {
    }
}