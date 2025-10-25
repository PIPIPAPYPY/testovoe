<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Сервис мониторинга кеша
 * 
 * Отслеживает метрики производительности кеша и статистику использования
 */
class CacheMetricsService
{
    private const METRICS_PREFIX = 'cache_metrics:';
    private const HIT_RATE_KEY = 'hit_rate';
    private const MISS_RATE_KEY = 'miss_rate';
    private const SLOW_OPERATIONS_KEY = 'slow_operations';
    private const MEMORY_USAGE_KEY = 'memory_usage';

    public function __construct(
        private CacheService $cacheService,
        private CacheKeyGenerator $keyGenerator
    ) {}

    /**
     * Записать метрику попадания в кеш
     */
    public function recordHit(string $key, array $tags = [], float $duration = 0): void
    {
        $this->incrementMetric('hits');
        $this->recordOperation($key, $tags, $duration, 'hit');
    }

    /**
     * Записать метрику промаха кеша
     */
    public function recordMiss(string $key, array $tags = [], float $duration = 0): void
    {
        $this->incrementMetric('misses');
        $this->recordOperation($key, $tags, $duration, 'miss');
    }

    /**
     * Записать медленную операцию
     */
    public function recordSlowOperation(string $key, array $tags = [], float $duration = 0): void
    {
        if ($duration > 0.1) { // Больше 100ms
            $this->recordOperation($key, $tags, $duration, 'slow');
            
            Log::warning('Slow cache operation detected', [
                'key' => $key,
                'tags' => $tags,
                'duration' => $duration
            ]);
        }
    }

    /**
     * Получить статистику попаданий
     */
    public function getHitRate(): float
    {
        $hits = $this->getMetric('hits');
        $misses = $this->getMetric('misses');
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Получить статистику промахов
     */
    public function getMissRate(): float
    {
        return 100 - $this->getHitRate();
    }

    /**
     * Получить общую статистику
     */
    public function getOverallStats(): array
    {
        return [
            'hits' => $this->getMetric('hits'),
            'misses' => $this->getMetric('misses'),
            'hit_rate' => $this->getHitRate(),
            'miss_rate' => $this->getMissRate(),
            'total_operations' => $this->getMetric('hits') + $this->getMetric('misses'),
            'slow_operations' => $this->getMetric('slow_operations'),
            'memory_usage' => $this->getMemoryUsage(),
            'cache_size' => $this->getCacheSize(),
        ];
    }

    /**
     * Получить статистику по тегам
     */
    public function getTagStats(): array
    {
        $tags = $this->getAllTags();
        $stats = [];

        foreach ($tags as $tag) {
            $stats[$tag] = [
                'hits' => $this->getMetric("tag:{$tag}:hits"),
                'misses' => $this->getMetric("tag:{$tag}:misses"),
                'operations' => $this->getMetric("tag:{$tag}:operations"),
            ];
        }

        return $stats;
    }

    /**
     * Получить статистику по ключам
     */
    public function getKeyStats(int $limit = 100): array
    {
        $keys = $this->getTopKeys($limit);
        $stats = [];

        foreach ($keys as $key) {
            $stats[$key] = [
                'hits' => $this->getMetric("key:{$key}:hits"),
                'misses' => $this->getMetric("key:{$key}:misses"),
                'avg_duration' => $this->getMetric("key:{$key}:avg_duration"),
            ];
        }

        return $stats;
    }

    /**
     * Получить использование памяти
     */
    public function getMemoryUsage(): array
    {
        try {
            if (config('cache.default') === 'array') {
                return [
                    'used_memory' => 0,
                    'used_memory_human' => '0B',
                    'used_memory_peak' => 0,
                    'used_memory_peak_human' => '0B',
                ];
            }

            $info = Redis::info('memory');
            return [
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'used_memory_peak' => $info['used_memory_peak'] ?? 0,
                'used_memory_peak_human' => $info['used_memory_peak_human'] ?? '0B',
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get memory usage', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Получить размер кеша
     */
    public function getCacheSize(): int
    {
        try {
            if (config('cache.default') === 'array') {
                return 0;
            }

            $keys = Redis::keys('*');
            return count($keys);
        } catch (\Exception $e) {
            Log::warning('Failed to get cache size', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Очистить все метрики
     */
    public function clearMetrics(): bool
    {
        try {
            $keys = Redis::keys(self::METRICS_PREFIX . '*');
            if (!empty($keys)) {
                Redis::del($keys);
            }
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to clear metrics', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Экспортировать метрики для мониторинга
     */
    public function exportMetrics(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'overall' => $this->getOverallStats(),
            'tags' => $this->getTagStats(),
            'top_keys' => $this->getKeyStats(50),
            'memory' => $this->getMemoryUsage(),
        ];
    }

    /**
     * Записать операцию
     */
    private function recordOperation(string $key, array $tags, float $duration, string $type): void
    {
        try {
            $this->incrementMetric("operations:{$type}");
            
            $this->incrementMetric("key:{$key}:{$type}");
            $this->updateAverageDuration($key, $duration);
            
            foreach ($tags as $tag) {
                $this->incrementMetric("tag:{$tag}:{$type}");
                $this->incrementMetric("tag:{$tag}:operations");
            }
            
            if ($duration > 0.1) {
                $this->incrementMetric('slow_operations');
            }
            
        } catch (\Exception $e) {
            Log::warning('Failed to record operation', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Увеличить метрику
     */
    private function incrementMetric(string $metric): void
    {
        try {
            $key = self::METRICS_PREFIX . $metric;
            Redis::incr($key);
            Redis::expire($key, 86400); // 24 часа
        } catch (\Exception $e) {
            Log::warning('Failed to increment metric', [
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Получить значение метрики
     */
    private function getMetric(string $metric): int
    {
        try {
            $key = self::METRICS_PREFIX . $metric;
            return (int) Redis::get($key);
        } catch (\Exception $e) {
            Log::warning('Failed to get metric', [
                'metric' => $metric,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Обновить среднюю длительность
     */
    private function updateAverageDuration(string $key, float $duration): void
    {
        try {
            $avgKey = self::METRICS_PREFIX . "key:{$key}:avg_duration";
            $countKey = self::METRICS_PREFIX . "key:{$key}:count";
            
            $count = (int) Redis::get($countKey) + 1;
            $currentAvg = (float) Redis::get($avgKey);
            
            $newAvg = (($currentAvg * ($count - 1)) + $duration) / $count;
            
            Redis::set($avgKey, $newAvg);
            Redis::set($countKey, $count);
            Redis::expire($avgKey, 86400);
            Redis::expire($countKey, 86400);
            
        } catch (\Exception $e) {
            Log::warning('Failed to update average duration', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Получить все теги
     */
    private function getAllTags(): array
    {
        try {
            $keys = Redis::keys(self::METRICS_PREFIX . 'tag:*:operations');
            $tags = [];
            
            foreach ($keys as $key) {
                $tag = str_replace([self::METRICS_PREFIX . 'tag:', ':operations'], '', $key);
                $tags[] = $tag;
            }
            
            return $tags;
        } catch (\Exception $e) {
            Log::warning('Failed to get all tags', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Получить топ ключей
     */
    private function getTopKeys(int $limit): array
    {
        try {
            $keys = Redis::keys(self::METRICS_PREFIX . 'key:*:hits');
            $keyStats = [];
            
            foreach ($keys as $key) {
                $keyName = str_replace([self::METRICS_PREFIX . 'key:', ':hits'], '', $key);
                $hits = (int) Redis::get($key);
                $keyStats[$keyName] = $hits;
            }
            
            arsort($keyStats);
            return array_slice(array_keys($keyStats), 0, $limit);
            
        } catch (\Exception $e) {
            Log::warning('Failed to get top keys', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
