<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

/**
 * Команда для проверки работоспособности системы
 */
class SystemCheckCommand extends Command
{
    protected $signature = 'system:check';
    protected $description = 'Проверить работоспособность всех компонентов системы';

    public function handle(): int
    {
        $this->info('=== Проверка работоспособности системы ===');
        $this->newLine();

        $checks = [
            'Проверка базы данных' => [$this, 'checkDatabase'],
            'Проверка Redis' => [$this, 'checkRedis'],
            'Проверка кеша' => [$this, 'checkCache'],
            'Проверка маршрутов' => [$this, 'checkRoutes'],
            'Проверка конфигурации' => [$this, 'checkConfig'],
        ];

        $results = [];
        foreach ($checks as $name => $callback) {
            $this->info("{$name}...");
            try {
                $result = $callback();
                $results[$name] = $result;
                if ($result) {
                    $this->line("  ✓ {$name} - OK");
                } else {
                    $this->error("  ✗ {$name} - FAILED");
                }
            } catch (\Exception $e) {
                $this->error("  ✗ {$name} - ERROR: {$e->getMessage()}");
                $results[$name] = false;
            }
            $this->newLine();
        }

        $this->info('=== Результаты проверки ===');
        $allPassed = true;
        foreach ($results as $name => $result) {
            $status = $result ? '✓ PASS' : '✗ FAIL';
            $this->line("{$name}: {$status}");
            if (!$result) {
                $allPassed = false;
            }
        }

        return $allPassed ? self::SUCCESS : self::FAILURE;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
            return count($tables) > 0;
        } catch (\Exception $e) {
            $this->error("  Ошибка БД: {$e->getMessage()}");
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            if (config('cache.default') === 'redis') {
                Redis::connection()->ping();
            }
            return true;
        } catch (\Exception $e) {
            $this->error("  Ошибка Redis: {$e->getMessage()}");
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            $key = 'system_check_' . time();
            Cache::put($key, 'test', 60);
            $value = Cache::get($key);
            Cache::forget($key);
            return $value === 'test';
        } catch (\Exception $e) {
            $this->error("  Ошибка кеша: {$e->getMessage()}");
            return false;
        }
    }

    private function checkRoutes(): bool
    {
        try {
            $routes = Route::getRoutes();
            return $routes->count() > 0;
        } catch (\Exception $e) {
            $this->error("  Ошибка маршрутов: {$e->getMessage()}");
            return false;
        }
    }

    private function checkConfig(): bool
    {
        try {
            $appName = config('app.name');
            $dbConnection = config('database.default');
            return !empty($appName) && !empty($dbConnection);
        } catch (\Exception $e) {
            $this->error("  Ошибка конфигурации: {$e->getMessage()}");
            return false;
        }
    }
}
