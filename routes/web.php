<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskWebController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tasks', [TaskWebController::class, 'index'])->name('tasks.index');
