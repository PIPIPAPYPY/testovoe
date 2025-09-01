<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthController;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Выход
    Route::post('/logout', [AuthController::class, 'logout']);

    // Маршруты для задач (привязанные к пользователю)
    Route::apiResource('tasks', TaskController::class);
});
