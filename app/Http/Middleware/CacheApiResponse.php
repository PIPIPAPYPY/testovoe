<?php

namespace App\Http\Middleware;

use App\Services\Cache\CacheService;
use App\Services\Cache\CacheKeyGenerator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Middleware для кеширования API ответов
 * 
 * Кеширует GET запросы с поддержкой ETag и Last-Modified заголовков
 */
class CacheApiResponse
{
    private const CACHE_TTL = 600;
    private const EXCLUDED_PATHS = [
        '/api/user',
        '/api/auth',
    ];

    public function __construct(
        private CacheService $cacheService,
        private CacheKeyGenerator $keyGenerator
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        if ($this->shouldExcludePath($request->path())) {
            return $next($request);
        }

        $etag = $this->getEtag($request);
        if ($etag && $request->header('If-None-Match') === $etag) {
            return response('', 304, [
                'ETag' => $etag,
                'Cache-Control' => 'private, max-age=' . self::CACHE_TTL,
            ]);
        }

        $lastModified = $this->getLastModified($request);
        if ($lastModified && $request->header('If-Modified-Since') === $lastModified) {
            return response('', 304, [
                'Last-Modified' => $lastModified,
                'Cache-Control' => 'private, max-age=' . self::CACHE_TTL,
            ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            $this->cacheResponse($request, $response);
        }

        return $response;
    }

    /**
     * Проверить, нужно ли исключить путь из кеширования
     */
    private function shouldExcludePath(string $path): bool
    {
        foreach (self::EXCLUDED_PATHS as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить ETag для запроса
     */
    private function getEtag(Request $request): ?string
    {
        $cacheKey = $this->getCacheKey($request);
        $tags = $this->getCacheTags($request);
        
        $etagKey = $cacheKey . ':etag';
        return $this->cacheService->get($etagKey, $tags);
    }

    /**
     * Получить Last-Modified для запроса
     */
    private function getLastModified(Request $request): ?string
    {
        $cacheKey = $this->getCacheKey($request);
        $tags = $this->getCacheTags($request);
        
        $lastModifiedKey = $cacheKey . ':last_modified';
        return $this->cacheService->get($lastModifiedKey, $tags);
    }

    /**
     * Кешировать ответ
     */
    private function cacheResponse(Request $request, Response $response): void
    {
        try {
            $cacheKey = $this->getCacheKey($request);
            $tags = $this->getCacheTags($request);
            
            $this->cacheService->put(
                $cacheKey,
                $response->getContent(),
                self::CACHE_TTL,
                $tags
            );

            $etag = '"' . md5($response->getContent()) . '"';
            $this->cacheService->put(
                $cacheKey . ':etag',
                $etag,
                self::CACHE_TTL,
                $tags
            );

            $lastModified = gmdate('D, d M Y H:i:s', time()) . ' GMT';
            $this->cacheService->put(
                $cacheKey . ':last_modified',
                $lastModified,
                self::CACHE_TTL,
                $tags
            );

            $response->headers->set('ETag', $etag);
            $response->headers->set('Last-Modified', $lastModified);
            $response->headers->set('Cache-Control', 'private, max-age=' . self::CACHE_TTL);

        } catch (\Exception $e) {
            Log::warning('Failed to cache API response', [
                'url' => $request->url(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Получить ключ кеша для запроса
     */
    private function getCacheKey(Request $request): string
    {
        $endpoint = $this->extractEndpoint($request->path());
        $params = $request->query();
        $userId = Auth::id();

        return $this->keyGenerator->apiResponse($endpoint, $params, $userId);
    }

    /**
     * Получить теги кеша для запроса
     */
    private function getCacheTags(Request $request): array
    {
        $endpoint = $this->extractEndpoint($request->path());
        $userId = Auth::id();

        return $this->cacheService->getApiTags($endpoint, $userId);
    }

    /**
     * Извлечь endpoint из пути
     */
    private function extractEndpoint(string $path): string
    {
        $endpoint = str_replace('/api/', '', $path);
        
        $endpoint = preg_replace('/\/\d+/', '/{id}', $endpoint);
        
        return $endpoint;
    }

    /**
     * Очистить кеш для endpoint
     */
    public function clearEndpointCache(string $endpoint, ?int $userId = null): bool
    {
        $tags = $this->cacheService->getApiTags($endpoint, $userId);
        return $this->cacheService->flushTags($tags);
    }

    /**
     * Очистить весь кеш API
     */
    public function clearAllApiCache(): bool
    {
        return $this->cacheService->flushTags(['api']);
    }
}
