<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Маршруты для задач
Route::apiResource('tasks', TaskController::class);
