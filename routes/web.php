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
 * Список задач пользователя
 * Отображает веб-интерфейс с задачами текущего пользователя, фильтрацией и пагинацией
 * Доступно: только авторизованным пользователям
 * Middleware: auth - проверка авторизации, compress - сжатие ответа
 */
Route::get('/tasks', [TaskWebController::class, 'index'])->middleware(['auth', 'compress'])->name('tasks.index');

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
