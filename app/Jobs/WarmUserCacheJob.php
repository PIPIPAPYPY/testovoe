<?php

namespace App\Jobs;

use App\Services\Cache\UserCacheService;
use App\Services\Cache\StaticCacheService;
use App\Services\Analytics\TaskAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для прогрева кеша пользователя
 * 
 * Выполняется асинхронно для прогрева кеша конкретного пользователя
 */
class WarmUserCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 минут
    public int $tries = 3;

    public function __construct(
        private int $userId,
        private array $options = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        UserCacheService $userCacheService,
        StaticCacheService $staticCacheService,
        TaskAnalyticsService $analyticsService
    ): void {
        try {
            Log::info('Starting cache warming for user', ['user_id' => $this->userId]);

            $startTime = microtime(true);

            if ($this->shouldWarm('user_data')) {
                $userCacheService->warmUserCache($this->userId);
                Log::info('User data cache warmed', ['user_id' => $this->userId]);
            }

            if ($this->shouldWarm('analytics')) {
                $analyticsService->warmAnalyticsCache($this->userId);
                Log::info('Analytics cache warmed', ['user_id' => $this->userId]);
            }

            if ($this->shouldWarm('static') && !$staticCacheService->isStaticDataCached()) {
                $staticCacheService->warmStaticCache();
                Log::info('Static data cache warmed', ['user_id' => $this->userId]);
            }

            $duration = round(microtime(true) - $startTime, 2);
            
            Log::info('Cache warming completed', [
                'user_id' => $this->userId,
                'duration' => $duration,
                'options' => $this->options
            ]);

        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Determine if the job should warm specific cache type
     */
    private function shouldWarm(string $type): bool
    {
        if (empty($this->options['types'])) {
            return true;
        }

        return in_array($type, $this->options['types']);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Cache warming job failed permanently', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'cache-warming',
            'user:' . $this->userId
        ];
    }

    /**
     * Get the cache key for this job
     */
    public function getCacheKey(): string
    {
        return "warm_cache_job:user:{$this->userId}";
    }

    /**
     * Check if cache warming is already in progress for this user
     */
    public static function isWarmingInProgress(int $userId): bool
    {
        try {
            $key = "warm_cache_job:user:{$userId}";
            $cacheService = app(\App\Services\Cache\CacheService::class);
            return $cacheService->has($key);
        } catch (\Exception $e) {
            Log::warning('Failed to check cache warming status', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark cache warming as started
     */
    public function markAsStarted(): void
    {
        try {
            $key = $this->getCacheKey();
            $cacheService = app(\App\Services\Cache\CacheService::class);
            $cacheService->put($key, time(), 300); // 5 минут
        } catch (\Exception $e) {
            Log::warning('Failed to mark cache warming as started', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark cache warming as completed
     */
    public function markAsCompleted(): void
    {
        try {
            $key = $this->getCacheKey();
            $cacheService = app(\App\Services\Cache\CacheService::class);
            $cacheService->forget($key);
        } catch (\Exception $e) {
            Log::warning('Failed to mark cache warming as completed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
