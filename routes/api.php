<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;



// Маршруты для задач (публичные, без аутентификации)
Route::apiResource('tasks', TaskController::class);
