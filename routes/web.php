<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskWebController;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tasks', [TaskWebController::class, 'index'])->name('tasks.index');

// Аутентификация
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
