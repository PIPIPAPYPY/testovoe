<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

// Публичные маршруты для задач (без аутентификации)
Route::apiResource('tasks', TaskController::class);