<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;

/**
 * Регистрация нового пользователя через API
 * Создает нового пользователя и возвращает токен Sanctum для авторизации
 * Доступно: всем пользователям (анонимным)
 * Middleware: throttle:5,1 - ограничение 5 попыток регистрации в минуту
 * Возвращает: JSON с данными пользователя и токеном
 */
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

/**
 * Альтернативный маршрут регистрации для совместимости
 * Дублирует функциональность /auth/register для клиентов, ожидающих /register
 */
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

/**
 * Авторизация пользователя через API
 * Проверяет учетные данные и возвращает токен Sanctum для последующих запросов
 * Доступно: всем пользователям (анонимным)
 * Middleware: throttle:5,1 - ограничение 5 попыток входа в минуту
 * Возвращает: JSON с данными пользователя и токеном
 */
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

/**
 * Альтернативный маршрут входа для совместимости
 * Дублирует функциональность /auth/login для клиентов, ожидающих /login
 */
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

/**
 * Группа защищенных API маршрутов
 * Все маршруты в этой группе требуют авторизации через Sanctum токены
 * Доступно: только авторизованным пользователям с валидным токеном
 * Middleware: auth:sanctum - проверка Sanctum токена
 */
Route::name('api.')->middleware('auth:sanctum')->group(function () {
    
    /**
     * Выход пользователя из API
     * Удаляет все токены Sanctum пользователя и завершает сессию
     * Доступно: только авторизованным пользователям
     * Возвращает: JSON с подтверждением выхода
     */
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    /**
     * REST API для управления задачами пользователя
     * Предоставляет полный набор CRUD операций:
     * - GET /api/tasks - получить список задач с фильтрацией и сортировкой
     * - POST /api/tasks - создать новую задачу
     * - GET /api/tasks/{id} - получить конкретную задачу
     * - PUT /api/tasks/{id} - обновить существующую задачу
     * - DELETE /api/tasks/{id} - удалить задачу
     * Доступно: только авторизованным пользователям
     * Пользователи видят только свои задачи (автоматическая фильтрация по user_id)
     */
    Route::apiResource('tasks', TaskController::class);

    /**
     * API для аналитики задач пользователя
     * Предоставляет различные виды аналитических данных и графиков:
     * - GET /api/analytics/completed-tasks-chart - график выполненных задач
     * - GET /api/analytics/category-chart - график по категориям
     * - GET /api/analytics/tag-chart - график по тегам
     * - GET /api/analytics/productive-days-chart - самые продуктивные дни
     * - GET /api/analytics/overall-stats - общая статистика
     * - GET /api/analytics/categories - доступные категории
     * - GET /api/analytics/tags - доступные теги
     * 
     * Доступно: только авторизованным пользователям
     * Данные фильтруются по текущему пользователю
     */
    Route::prefix('analytics')->group(function () {
        Route::get('completed-tasks-chart', [App\Http\Controllers\AnalyticsController::class, 'getCompletedTasksChart']);
        Route::get('category-chart', [App\Http\Controllers\AnalyticsController::class, 'getCategoryChart']);
        Route::get('tag-chart', [App\Http\Controllers\AnalyticsController::class, 'getTagChart']);
        Route::get('productive-days-chart', [App\Http\Controllers\AnalyticsController::class, 'getProductiveDaysChart']);
        Route::get('overall-stats', [App\Http\Controllers\AnalyticsController::class, 'getOverallStats']);
        Route::get('categories', [App\Http\Controllers\AnalyticsController::class, 'getAvailableCategories']);
        Route::get('tags', [App\Http\Controllers\AnalyticsController::class, 'getAvailableTags']);
    });
});

/**
 * Получение данных текущего авторизованного пользователя
 * Возвращает информацию о пользователе по Sanctum токену
 * Доступно: только авторизованным пользователям
 * Middleware: auth:sanctum - проверка Sanctum токена
 * Возвращает: JSON с данными пользователя
 */
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
