<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/**
 * Консольная команда для отображения вдохновляющих цитат
 * Выводит случайную мотивационную цитату в консоль
 * Доступно: разработчикам через Artisan CLI
 * Использование: php artisan inspire
 * Назначение: демонстрация создания кастомных Artisan команд
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
