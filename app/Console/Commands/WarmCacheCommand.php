<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Cache\CacheService;
use App\Services\Cache\CacheKeyGenerator;
use App\Services\Cache\UserCacheService;
use App\Services\Cache\StaticCacheService;
use App\Services\Analytics\TaskAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Команда для прогрева кеша
 * 
 * Прогревает кеш для активных пользователей и статических данных
 */
class WarmCacheCommand extends Command
{
    protected $signature = 'cache:warm 
                            {--users=10 : Количество пользователей для прогрева}
                            {--force : Принудительно прогреть весь кеш}
                            {--analytics : Прогреть только аналитику}
                            {--static : Прогреть только статические данные}';

    protected $description = 'Прогреть кеш для активных пользователей и статических данных';

    public function __construct(
        private CacheService $cacheService,
        private CacheKeyGenerator $keyGenerator,
        private UserCacheService $userCacheService,
        private StaticCacheService $staticCacheService,
        private TaskAnalyticsService $analyticsService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Начинаем прогрев кеша...');

        $startTime = microtime(true);
        $warmedCount = 0;

        try {
            if ($this->option('static') || $this->option('force')) {
                $this->warmStaticData();
                $warmedCount++;
            }

            if ($this->option('analytics') || $this->option('force')) {
                $warmedCount += $this->warmAnalytics();
            }

            if (!$this->option('analytics') && !$this->option('static')) {
                $warmedCount += $this->warmFullCache();
            }

            $duration = round(microtime(true) - $startTime, 2);
            
            $this->info("Прогрев кеша завершен за {$duration} секунд");
            $this->info("Прогрето компонентов: {$warmedCount}");
            
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Ошибка при прогреве кеша: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Прогреть статические данные
     */
    private function warmStaticData(): void
    {
        $this->info('Прогреваем статические данные...');
        
        $this->staticCacheService->warmStaticCache();
        
        $this->line('✓ Статические данные прогреты');
    }

    /**
     * Прогреть аналитику
     */
    private function warmAnalytics(): int
    {
        $this->info('Прогреваем аналитику...');
        
        $userCount = (int) $this->option('users');
        $activeUsers = $this->getActiveUsers($userCount);
        
        $progressBar = $this->output->createProgressBar(count($activeUsers));
        $progressBar->start();
        
        foreach ($activeUsers as $user) {
            try {
                $this->analyticsService->warmAnalyticsCache($user->id);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->warn("Ошибка прогрева аналитики для пользователя {$user->id}: {$e->getMessage()}");
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->line("✓ Аналитика прогрета для " . count($activeUsers) . " пользователей");
        
        return count($activeUsers);
    }

    /**
     * Полный прогрев кеша
     */
    private function warmFullCache(): int
    {
        $this->info('Выполняем полный прогрев кеша...');
        
        $warmedCount = 0;
        
        $this->warmStaticData();
        $warmedCount++;
        
        $warmedCount += $this->warmUserData();
        
        $warmedCount += $this->warmAnalytics();
        
        return $warmedCount;
    }

    /**
     * Прогреть пользовательские данные
     */
    private function warmUserData(): int
    {
        $this->info('Прогреваем пользовательские данные...');
        
        $userCount = (int) $this->option('users');
        $activeUsers = $this->getActiveUsers($userCount);
        
        $progressBar = $this->output->createProgressBar(count($activeUsers));
        $progressBar->start();
        
        foreach ($activeUsers as $user) {
            try {
                $this->userCacheService->warmUserCache($user->id);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->warn("Ошибка прогрева данных пользователя {$user->id}: {$e->getMessage()}");
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->line("✓ Пользовательские данные прогреты для " . count($activeUsers) . " пользователей");
        
        return count($activeUsers);
    }

    /**
     * Получить активных пользователей
     */
    private function getActiveUsers(int $limit): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereHas('tasks')
            ->orWhere('updated_at', '>=', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Очистить кеш перед прогревом
     */
    private function clearCache(): void
    {
        if ($this->option('force')) {
            $this->info('Очищаем кеш...');
            $this->cacheService->flush();
            $this->line('✓ Кеш очищен');
        }
    }

    /**
     * Показать статистику кеша
     */
    private function showCacheStats(): void
    {
        $this->info('Статистика кеша:');
        
        try {
            $stats = [
                'Размер кеша' => $this->getCacheSize(),
                'Использование памяти' => $this->getMemoryUsage(),
            ];
            
            foreach ($stats as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
        } catch (\Exception $e) {
            $this->warn("Не удалось получить статистику кеша: {$e->getMessage()}");
        }
    }

    /**
     * Получить размер кеша
     */
    private function getCacheSize(): string
    {
        try {
            $keys = \Illuminate\Support\Facades\Redis::keys('*');
            return count($keys) . ' ключей';
        } catch (\Exception $e) {
            return 'Недоступно';
        }
    }

    /**
     * Получить использование памяти
     */
    private function getMemoryUsage(): string
    {
        try {
            $info = \Illuminate\Support\Facades\Redis::info('memory');
            return $info['used_memory_human'] ?? 'Недоступно';
        } catch (\Exception $e) {
            return 'Недоступно';
        }
    }
}
