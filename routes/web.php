<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskWebController;
use App\Http\Controllers\WebAuthController;

/**
 * Главная страница приложения
 * Отображает welcome.blade.php с информацией о проекте и возможностях системы
 * Доступно: всем пользователям (анонимным и авторизованным)
 * Middleware: compress - сжатие ответа для оптимизации
 */
Route::get('/', function () {
    return view('welcome');
})->middleware('compress');

/**
 * Страница регистрации нового пользователя
 * Отображает форму регистрации auth.register
 * Доступно: всем пользователям (анонимным)
 */
Route::get('/register', function () {
    return view('auth.register');
});

/**
 * Веб-маршруты для управления задачами
 * Используют веб-сессии вместо API токенов
 * Доступно: только авторизованным пользователям
 */
Route::middleware(['auth', 'compress'])->group(function () {
    Route::get('/tasks', [TaskWebController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskWebController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskWebController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskWebController::class, 'destroy'])->name('tasks.destroy');
});

/**
 * Аналитика задач пользователя
 * Отображает страницу с графиками и статистикой по задачам пользователя
 * Доступно: только авторизованным пользователям
 * Middleware: auth - проверка авторизации, compress - сжатие ответа
 */
Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->middleware(['auth', 'compress'])->name('analytics.index');

/**
 * Веб-endpoints для аналитики (используют веб-сессии вместо API токенов)
 * Доступно: только авторизованным пользователям через веб-сессию
 */
Route::middleware(['auth', 'compress'])->prefix('analytics')->group(function () {
    Route::get('task-creation-chart', [App\Http\Controllers\AnalyticsController::class, 'getTaskCreationChart'])->name('analytics.task-creation-chart');
    Route::get('completion-chart', [App\Http\Controllers\AnalyticsController::class, 'getCompletionChart'])->name('analytics.completion-chart');
    Route::get('priority-chart', [App\Http\Controllers\AnalyticsController::class, 'getPriorityChart'])->name('analytics.priority-chart');
    Route::get('weekly-activity-chart', [App\Http\Controllers\AnalyticsController::class, 'getWeeklyActivityChart'])->name('analytics.weekly-activity-chart');

    Route::get('overall-stats', [App\Http\Controllers\AnalyticsController::class, 'getOverallStats'])->name('analytics.overall-stats');
});

/**
 * Страница тестирования API
 * Отображает интерактивную страницу для тестирования всех API endpoints
 * Доступно: всем пользователям (анонимным и авторизованным)
 */
Route::get('/api-test', function () {
    return view('api-test');
});

/**
 * Форма входа пользователя
 * Отображает страницу авторизации auth.login
 * Доступно: всем пользователям (анонимным)
 */
Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');

/**
 * Обработка входа пользователя
 * Проверяет учетные данные и авторизует пользователя через веб-сессию
 * Доступно: всем пользователям (анонимным)
 * Middleware: web - веб-сессии, throttle:5,1 - ограничение 5 попыток в минуту
 */
Route::post('/login', [WebAuthController::class, 'login'])->middleware(['web', 'throttle:5,1']);

/**
 * Выход пользователя из системы
 * Завершает веб-сессию пользователя и перенаправляет на главную страницу
 * Доступно: только авторизованным пользователям
 * Middleware: auth - проверка авторизации
 */
Route::post('/logout', [WebAuthController::class, 'logout'])->middleware('auth')->name('logout');

