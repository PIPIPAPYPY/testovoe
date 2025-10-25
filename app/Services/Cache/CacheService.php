<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Централизованный сервис управления кешем
 * 
 * Обеспечивает единообразное управление кешем с поддержкой тегов
 * и автоматической инвалидацией
 */
class CacheService
{
    public const TTL_ANALYTICS = 300;
    public const TTL_LISTS = 180;
    public const TTL_USER = 900;
    public const TTL_STATIC = 3600;
    public const TTL_API = 600;

    /**
     * Получить данные из кеша с тегами
     */
    public function get(?string $key, array $tags = [], $default = null)
    {
        try {
            if ($key === null || $key === '') {
                return $default;
            }
            
            if (empty($tags)) {
                return Cache::get($key, $default);
            }

            if (config('cache.default') === 'array') {
                return Cache::get($key, $default);
            }

            return Cache::tags($tags)->get($key, $default);
        } catch (\Exception $e) {
            Log::warning('Cache get failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Сохранить данные в кеш с тегами
     */
    public function put(string $key, $value, int $ttl = null, array $tags = []): bool
    {
        try {
            if (empty($key) || $key === '') {
                return false;
            }
            
            if (empty($tags)) {
                return Cache::put($key, $value, $ttl);
            }

            if (config('cache.default') === 'array') {
                return Cache::put($key, $value, $ttl);
            }

            return Cache::tags($tags)->put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::warning('Cache put failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить или вычислить данные с кешированием
     */
    public function remember(string $key, callable $callback, int $ttl = null, array $tags = [])
    {
        try {
            if (empty($tags)) {
                return Cache::remember($key, $ttl, $callback);
            }

            if (config('cache.default') === 'array') {
                return Cache::remember($key, $ttl, $callback);
            }

            return Cache::tags($tags)->remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return $callback();
        }
    }

    /**
     * Удалить данные из кеша
     */
    public function forget(string $key, array $tags = []): bool
    {
        try {
            if (empty($tags)) {
                return Cache::forget($key);
            }

            if (config('cache.default') === 'array') {
                return Cache::forget($key);
            }

            return Cache::tags($tags)->forget($key);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Очистить кеш по тегам
     */
    public function flushTags(array $tags): bool
    {
        try {
            if (config('cache.default') === 'array') {
                $this->flushArrayCacheByPattern($tags);
                return true;
            }

            return Cache::tags($tags)->flush();
        } catch (\Exception $e) {
            Log::warning('Cache flush tags failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Очистить кеш в array драйвере по паттерну тегов
     * Это приблизительная имитация работы с тегами
     */
    private function flushArrayCacheByPattern(array $tags): void
    {
        Log::info('Array cache driver: flushing all cache due to tag limitation', [
            'requested_tags' => $tags,
            'note' => 'Array driver does not support tag-based isolation'
        ]);
        
        Cache::flush();
    }

    /**
     * Очистить весь кеш
     */
    public function flush(): bool
    {
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::warning('Cache flush failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Проверить существование ключа в кеше
     */
    public function has(string $key, array $tags = []): bool
    {
        try {
            if (empty($tags)) {
                return Cache::has($key);
            }

            if (config('cache.default') === 'array') {
                return Cache::has($key);
            }

            return Cache::tags($tags)->has($key);
        } catch (\Exception $e) {
            Log::warning('Cache has failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Получить TTL для типа данных
     */
    public function getTtl(string $type): int
    {
        return match ($type) {
            'analytics' => self::TTL_ANALYTICS,
            'lists' => self::TTL_LISTS,
            'user' => self::TTL_USER,
            'static' => self::TTL_STATIC,
            'api' => self::TTL_API,
            default => self::TTL_LISTS,
        };
    }

    /**
     * Создать теги для пользователя
     */
    public function getUserTags(int $userId): array
    {
        return [
            'user:' . $userId,
            'tasks',
            'analytics'
        ];
    }

    /**
     * Создать теги для аналитики
     */
    public function getAnalyticsTags(int $userId): array
    {
        return [
            'analytics',
            'user:' . $userId,
            'analytics:creation',
            'analytics:completion',
            'analytics:priorities',
            'analytics:weekly'
        ];
    }

    /**
     * Создать теги для API
     */
    public function getApiTags(string $endpoint, ?int $userId = null): array
    {
        $tags = ['api', 'api:' . $endpoint];
        
        if ($userId) {
            $tags[] = 'user:' . $userId;
        }
        
        return $tags;
    }

    /**
     * Создать теги для статических данных
     */
    public function getStaticTags(): array
    {
        return ['static', 'config'];
    }
}
