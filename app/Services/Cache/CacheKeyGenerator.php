<?php

namespace App\Services\Cache;

/**
 * Генератор ключей кеша
 * 
 * Генерирует ключи кеша единообразно и без конфликтов.
 * Одинаковые параметры всегда дают одинаковый ключ,
 * разные параметры - разные ключи.
 */
class CacheKeyGenerator
{
    private const PREFIX_SEPARATOR = ':';
    private const VERSION = 'v1';

    /**
     * Создать ключ для задач пользователя
     */
    public function userTasks(int $userId, array $filters = []): string
    {
        $key = 'tasks:user:' . $userId;
        
        if (!empty($filters)) {
            $filterHash = $this->generateFilterHash($filters);
            $key .= ':filters:' . $filterHash;
        }
        
        return $this->addVersion($key);
    }

    /**
     * Создать ключ для поиска задач
     */
    public function taskSearch(int $userId, string $searchTerm, array $filters = []): string
    {
        $searchHash = md5(mb_strtolower(trim($searchTerm)));
        $key = 'tasks:search:user:' . $userId . ':term:' . $searchHash;
        
        if (!empty($filters)) {
            $filterHash = $this->generateFilterHash($filters);
            $key .= ':filters:' . $filterHash;
        }
        
        return $this->addVersion($key);
    }

    /**
     * Создать ключ для аналитики пользователя
     */
    public function analytics(int $userId, string $type, string $period = null): string
    {
        $key = 'analytics:user:' . $userId . ':type:' . $type;
        
        if ($period) {
            $key .= ':period:' . $period;
        }
        
        return $this->addVersion($key);
    }

    /**
     * Создать ключ для статистики задач
     */
    public function taskStats(int $userId): string
    {
        return $this->addVersion('tasks:stats:user:' . $userId);
    }

    /**
     * Создать ключ для пользовательских данных
     */
    public function userProfile(int $userId): string
    {
        return $this->addVersion('user:profile:' . $userId);
    }

    /**
     * Создать ключ для статических данных
     */
    public function staticData(string $type): string
    {
        return $this->addVersion('static:' . $type);
    }

    /**
     * Создать ключ для API ответа
     */
    public function apiResponse(string $endpoint, array $params = [], ?int $userId = null): string
    {
        $key = 'api:response:' . $endpoint;
        
        if ($userId) {
            $key .= ':user:' . $userId;
        }
        
        if (!empty($params)) {
            $paramHash = $this->generateFilterHash($params);
            $key .= ':params:' . $paramHash;
        }
        
        return $this->addVersion($key);
    }

    /**
     * Создать ключ для кеша списков
     */
    public function list(string $type, int $userId, array $options = []): string
    {
        $key = 'list:' . $type . ':user:' . $userId;
        
        if (!empty($options)) {
            $optionHash = $this->generateFilterHash($options);
            $key .= ':options:' . $optionHash;
        }
        
        return $this->addVersion($key);
    }

    /**
     * Создать ключ для кеша с пагинацией
     */
    public function paginated(string $baseKey, int $page, int $perPage): string
    {
        return $baseKey . ':page:' . $page . ':per_page:' . $perPage;
    }

    /**
     * Создать ключ для кеша с сортировкой
     */
    public function sorted(string $baseKey, string $sortBy, string $sortDir = 'asc'): string
    {
        return $baseKey . ':sort:' . $sortBy . ':' . $sortDir;
    }

    /**
     * Создать ключ для кеша с временным диапазоном
     */
    public function timeRange(string $baseKey, string $from, string $to): string
    {
        $fromHash = md5($from);
        $toHash = md5($to);
        return $baseKey . ':from:' . $fromHash . ':to:' . $toHash;
    }

    /**
     * Генерировать хеш для фильтров
     */
    private function generateFilterHash(array $filters): string
    {
        $cleanFilters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });
        
        ksort($cleanFilters);
        
        return md5(serialize($cleanFilters));
    }

    /**
     * Добавить версию к ключу
     */
    private function addVersion(string $key): string
    {
        return self::VERSION . self::PREFIX_SEPARATOR . $key;
    }

    /**
     * Создать префикс для группы ключей
     */
    public function createPrefix(string $group): string
    {
        return self::VERSION . self::PREFIX_SEPARATOR . $group;
    }

    /**
     * Проверить, принадлежит ли ключ к группе
     */
    public function belongsToGroup(string $key, string $group): bool
    {
        $prefix = $this->createPrefix($group);
        return str_starts_with($key, $prefix);
    }

    /**
     * Извлечь группу из ключа
     */
    public function extractGroup(string $key): ?string
    {
        $parts = explode(self::PREFIX_SEPARATOR, $key, 3);
        
        if (count($parts) < 3) {
            return null;
        }
        
        return $parts[1];
    }

    /**
     * Создать ключ для блокировки
     */
    public function lock(string $resource, string $identifier = null): string
    {
        $key = 'lock:' . $resource;
        
        if ($identifier) {
            $key .= ':' . $identifier;
        }
        
        return $this->addVersion($key);
    }

    /**
     * Создать ключ для счетчика
     */
    public function counter(string $name, array $context = []): string
    {
        $key = 'counter:' . $name;
        
        if (!empty($context)) {
            $contextHash = $this->generateFilterHash($context);
            $key .= ':' . $contextHash;
        }
        
        return $this->addVersion($key);
    }

    /**
     * Создать ключ для метрик
     */
    public function metrics(string $metric, string $timeframe = 'hour'): string
    {
        return $this->addVersion('metrics:' . $metric . ':' . $timeframe);
    }
}
