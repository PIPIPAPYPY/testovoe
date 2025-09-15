<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/**
 * Провайдер маршрутов приложения
 * 
 * Настраивает маршруты и ограничения скорости для API и веб-запросов
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * Путь к главной странице приложения
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Настроить маршруты и ограничения скорости приложения
     * @return void
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
