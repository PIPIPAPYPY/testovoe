<?php

namespace App\Services\Cache;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Сервис кеширования пользовательских данных
 * 
 * Кеширует профили пользователей, права доступа и метаданные токенов
 */
class UserCacheService
{
    public function __construct(
        private CacheService $cacheService,
        private CacheKeyGenerator $keyGenerator
    ) {}

    /**
     * Получить профиль пользователя из кеша
     */
    public function getUserProfile(int $userId): ?array
    {
        $key = $this->keyGenerator->userProfile($userId);
        $tags = $this->cacheService->getUserTags($userId);

        return $this->cacheService->remember(
            $key,
            function () use ($userId) {
                $user = User::find($userId);
                if (!$user) {
                    return null;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            },
            $this->cacheService->getTtl('user'),
            $tags
        );
    }

    /**
     * Получить права доступа пользователя
     */
    public function getUserPermissions(int $userId): array
    {
        $key = $this->keyGenerator->userProfile($userId) . ':permissions';
        $tags = $this->cacheService->getUserTags($userId);

        return $this->cacheService->remember(
            $key,
            function () use ($userId) {
                return [
                    'can_create_tasks' => true,
                    'can_edit_tasks' => true,
                    'can_delete_tasks' => true,
                    'can_view_analytics' => true,
                    'can_export_data' => false,
                ];
            },
            $this->cacheService->getTtl('user'),
            $tags
        );
    }

    /**
     * Получить метаданные токена Sanctum
     */
    public function getTokenMetadata(string $tokenId): ?array
    {
        $key = $this->keyGenerator->staticData('token_metadata') . ':' . $tokenId;
        $tags = $this->cacheService->getStaticTags();

        return $this->cacheService->remember(
            $key,
            function () use ($tokenId) {
                $token = DB::table('personal_access_tokens')
                    ->where('id', $tokenId)
                    ->first();

                if (!$token) {
                    return null;
                }

                return [
                    'id' => $token->id,
                    'user_id' => $token->tokenable_id,
                    'name' => $token->name,
                    'abilities' => json_decode($token->abilities, true),
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                ];
            },
            $this->cacheService->getTtl('user'),
            $tags
        );
    }

    /**
     * Получить активные сессии пользователя
     */
    public function getUserSessions(int $userId): array
    {
        $key = $this->keyGenerator->userProfile($userId) . ':sessions';
        $tags = $this->cacheService->getUserTags($userId);

        return $this->cacheService->remember(
            $key,
            function () use ($userId) {
                $tokens = DB::table('personal_access_tokens')
                    ->where('tokenable_id', $userId)
                    ->where('expires_at', '>', now())
                    ->orderBy('last_used_at', 'desc')
                    ->get();

                return $tokens->map(function ($token) {
                    return [
                        'id' => $token->id,
                        'name' => $token->name,
                        'last_used_at' => $token->last_used_at,
                        'created_at' => $token->created_at,
                    ];
                })->toArray();
            },
            $this->cacheService->getTtl('user'),
            $tags
        );
    }

    /**
     * Получить настройки пользователя
     */
    public function getUserSettings(int $userId): array
    {
        $key = $this->keyGenerator->userProfile($userId) . ':settings';
        $tags = $this->cacheService->getUserTags($userId);

        return $this->cacheService->remember(
            $key,
            function () use ($userId) {
                return [
                    'theme' => 'light',
                    'language' => 'ru',
                    'timezone' => 'Europe/Moscow',
                    'notifications' => [
                        'email' => true,
                        'push' => false,
                    ],
                    'tasks_per_page' => 12,
                    'default_priority' => 2,
                ];
            },
            $this->cacheService->getTtl('user'),
            $tags
        );
    }

    /**
     * Получить статистику активности пользователя
     */
    public function getUserActivityStats(int $userId): array
    {
        $key = $this->keyGenerator->userProfile($userId) . ':activity';
        $tags = $this->cacheService->getUserTags($userId);

        return $this->cacheService->remember(
            $key,
            function () use ($userId) {
                $stats = DB::table('tasks')
                    ->where('user_id', $userId)
                    ->selectRaw('
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as tasks_this_week,
                        MAX(created_at) as last_task_created
                    ', ['done', now()->subWeek()])
                    ->first();

                return [
                    'total_tasks' => $stats->total_tasks ?? 0,
                    'completed_tasks' => $stats->completed_tasks ?? 0,
                    'tasks_this_week' => $stats->tasks_this_week ?? 0,
                    'last_task_created' => $stats->last_task_created,
                    'completion_rate' => $stats->total_tasks > 0 
                        ? round(($stats->completed_tasks / $stats->total_tasks) * 100, 2) 
                        : 0,
                ];
            },
            $this->cacheService->getTtl('user'),
            $tags
        );
    }

    /**
     * Очистить кеш пользователя
     */
    public function clearUserCache(int $userId): bool
    {
        $tags = $this->cacheService->getUserTags($userId);
        return $this->cacheService->flushTags($tags);
    }

    /**
     * Очистить кеш всех пользователей
     */
    public function clearAllUsersCache(): bool
    {
        return $this->cacheService->flushTags(['users', 'user:*']);
    }

    /**
     * Обновить профиль пользователя в кеше
     */
    public function updateUserProfile(int $userId, array $data): bool
    {
        $this->clearUserCache($userId);
        
        $user = User::find($userId);
        if ($user) {
            $user->update($data);
        }
        
        return true;
    }

    /**
     * Получить текущего пользователя из кеша
     */
    public function getCurrentUser(): ?array
    {
        $userId = Auth::id();
        if (!$userId) {
            return null;
        }

        return $this->getUserProfile($userId);
    }

    /**
     * Проверить, кеширован ли пользователь
     */
    public function isUserCached(int $userId): bool
    {
        $key = $this->keyGenerator->userProfile($userId);
        $tags = $this->cacheService->getUserTags($userId);
        
        return $this->cacheService->has($key, $tags);
    }

    /**
     * Прогреть кеш пользователя
     */
    public function warmUserCache(int $userId): void
    {
        $this->getUserProfile($userId);
        $this->getUserPermissions($userId);
        $this->getUserSessions($userId);
        $this->getUserSettings($userId);
        $this->getUserActivityStats($userId);
    }
}
