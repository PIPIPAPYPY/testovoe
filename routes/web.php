<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskWebController;
use App\Http\Controllers\AuthWebController;

Route::get('/', function () { return redirect()->route('tasks.index'); });

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthWebController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthWebController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthWebController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/tasks', [TaskWebController::class, 'index'])->middleware('auth')->name('tasks.index');
