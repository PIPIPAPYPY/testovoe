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
        $this->app->singleton(\App\Services\TaskService::class);
    }

    /**
     * Выполнить начальную настройку приложения
     * @return void
     */
    public function boot(): void
    {
        \App\Models\Task::observe(\App\Observers\TaskObserver::class);
        
        $this->checkRedisHealth();
    }
    
    /**
     * Проверка здоровья Redis соединения
     * При недоступности Redis переключается на array драйвер
     */
    private function checkRedisHealth(): void
    {
        try {
            if (config('cache.default') !== 'redis') {
                return;
            }
            
            \Illuminate\Support\Facades\Cache::connection('redis')->ping();
            
            \Illuminate\Support\Facades\Log::info('Redis connection is healthy');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Redis connection failed, falling back to array driver', [
                'error' => $e->getMessage(),
                'fallback_driver' => 'array'
            ]);
            
            config(['cache.default' => 'array']);
            
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        }
    }
}
