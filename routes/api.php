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
 * Авторизация пользователя через API
 * Проверяет учетные данные и возвращает токен Sanctum для последующих запросов
 * Доступно: всем пользователям (анонимным)
 * Middleware: throttle:5,1 - ограничение 5 попыток входа в минуту
 * Возвращает: JSON с данными пользователя и токеном
 */
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

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
