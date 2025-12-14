<?php

namespace App\Services\Cache;

/**
 * Сервис кеширования статических данных
 * 
 * Кеширует справочники, конфигурацию и редко изменяемые данные
 */
class StaticCacheService
{
    public function __construct(
        private CacheService $cacheService,
        private CacheKeyGenerator $keyGenerator
    ) {}

    /**
     * Получить статусы задач
     */
    public function getStatuses(): array
    {
        $key = $this->keyGenerator->staticData('task_statuses');
        $tags = $this->cacheService->getStaticTags();

        return $this->cacheService->remember(
            $key,
            function () {
                return [
                    'todo' => [
                        'value' => 'todo',
                        'label' => 'К выполнению',
                        'color' => '#6b7280',
                        'icon' => 'clock'
                    ],
                    'in_progress' => [
                        'value' => 'in_progress',
                        'label' => 'В работе',
                        'color' => '#3b82f6',
                        'icon' => 'play'
                    ],
                    'done' => [
                        'value' => 'done',
                        'label' => 'Выполнено',
                        'color' => '#10b981',
                        'icon' => 'check'
                    ]
                ];
            },
            $this->cacheService->getTtl('static'),
            $tags
        );
    }

    /**
     * Получить приоритеты задач
     */
    public function getPriorities(): array
    {
        $key = $this->keyGenerator->staticData('task_priorities');
        $tags = $this->cacheService->getStaticTags();

        return $this->cacheService->remember(
            $key,
            function () {
                return [
                    1 => [
                        'value' => 1,
                        'label' => 'Высокий',
                        'color' => '#ef4444',
                        'icon' => 'exclamation-triangle'
                    ],
                    2 => [
                        'value' => 2,
                        'label' => 'Средний',
                        'color' => '#f59e0b',
                        'icon' => 'minus'
                    ],
                    3 => [
                        'value' => 3,
                        'label' => 'Низкий',
                        'color' => '#10b981',
                        'icon' => 'arrow-down'
                    ]
                ];
            },
            $this->cacheService->getTtl('static'),
            $tags
        );
    }

    /**
     * Получить конфигурацию приложения
     */
    public function getConfig(): array
    {
        $key = $this->keyGenerator->staticData('app_config');
        $tags = $this->cacheService->getStaticTags();

        return $this->cacheService->remember(
            $key,
            function () {
                return [
                    'app' => [
                        'name' => config('app.name'),
                        'version' => '1.0.0',
                        'timezone' => config('app.timezone'),
                        'locale' => config('app.locale'),
                    ],
                    'tasks' => [
                        'max_title_length' => 255,
                        'max_description_length' => 1000,
                        'default_priority' => 2,
                        'default_status' => 'todo',
                        'per_page_options' => [12, 24, 48, 96],
                        'default_per_page' => 12,
                    ],
                    'analytics' => [
                        'cache_ttl' => 300,
                        'max_period_days' => 365,
                        'chart_colors' => [
                            '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
                            '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
                        ]
                    ],
                    'api' => [
                        'rate_limit' => 60,
                        'cache_ttl' => 600,
                        'max_page_size' => 100,
                    ]
                ];
            },
            $this->cacheService->getTtl('static'),
            $tags
        );
    }

    /**
     * Получить категории задач (заглушка)
     */
    public function getCategories(): array
    {
        $key = $this->keyGenerator->staticData('task_categories');
        $tags = $this->cacheService->getStaticTags();

        return $this->cacheService->remember(
            $key,
            function () {
                return [
                    'work' => [
                        'value' => 'work',
                        'label' => 'Работа',
                        'color' => '#3b82f6',
                        'icon' => 'briefcase'
                    ],
                    'personal' => [
                        'value' => 'personal',
                        'label' => 'Личное',
                        'color' => '#10b981',
                        'icon' => 'user'
                    ],
                    'study' => [
                        'value' => 'study',
                        'label' => 'Учеба',
                        'color' => '#8b5cf6',
                        'icon' => 'academic-cap'
                    ],
                    'projects' => [
                        'value' => 'projects',
                        'label' => 'Проекты',
                        'color' => '#f59e0b',
                        'icon' => 'folder'
                    ],
                    'meetings' => [
                        'value' => 'meetings',
                        'label' => 'Встречи',
                        'color' => '#ef4444',
                        'icon' => 'users'
                    ]
                ];
            },
            $this->cacheService->getTtl('static'),
            $tags
        );
    }

    /**
     * Получить теги задач (заглушка)
     */
    public function getTags(): array
    {
        $key = $this->keyGenerator->staticData('task_tags');
        $tags = $this->cacheService->getStaticTags();

        return $this->cacheService->remember(
            $key,
            function () {
                return [
                    'urgent' => [
                        'value' => 'urgent',
                        'label' => 'Срочно',
                        'color' => '#ef4444'
                    ],
                    'important' => [
                        'value' => 'important',
                        'label' => 'Важно',
                        'color' => '#f59e0b'
                    ],
                    'project' => [
                        'value' => 'project',
                        'label' => 'Проект',
                        'color' => '#3b82f6'
                    ],
                    'meeting' => [
                        'value' => 'meeting',
                        'label' => 'Встреча',
                        'color' => '#8b5cf6'
                    ],
                    'call' => [
                        'value' => 'call',
                        'label' => 'Звонок',
                        'color' => '#10b981'
                    ],
                    'email' => [
                        'value' => 'email',
                        'label' => 'Email',
                        'color' => '#06b6d4'
                    ],
                    'documents' => [
                        'value' => 'documents',
                        'label' => 'Документы',
                        'color' => '#84cc16'
                    ]
                ];
            },
            $this->cacheService->getTtl('static'),
            $tags
        );
    }

    /**
     * Получить периоды для аналитики
     */
    public function getAnalyticsPeriods(): array
    {
        $key = $this->keyGenerator->staticData('analytics_periods');
        $tags = $this->cacheService->getStaticTags();

        return $this->cacheService->remember(
            $key,
            function () {
                return [
                    'day' => [
                        'value' => 'day',
                        'label' => 'По дням',
                        'days' => 7,
                        'format' => 'Y-m-d'
                    ],
                    'week' => [
                        'value' => 'week',
                        'label' => 'По неделям',
                        'days' => 56,
                        'format' => 'Y-W'
                    ],
                    'month' => [
                        'value' => 'month',
                        'label' => 'По месяцам',
                        'days' => 365,
                        'format' => 'Y-m'
                    ]
                ];
            },
            $this->cacheService->getTtl('static'),
            $tags
        );
    }

    /**
     * Получить настройки сортировки
     */
    public function getSortOptions(): array
    {
        $key = $this->keyGenerator->staticData('sort_options');
        $tags = $this->cacheService->getStaticTags();

        return $this->cacheService->remember(
            $key,
            function () {
                return [
                    'created_at' => [
                        'value' => 'created_at',
                        'label' => 'По дате создания',
                        'default_direction' => 'desc'
                    ],
                    'updated_at' => [
                        'value' => 'updated_at',
                        'label' => 'По дате обновления',
                        'default_direction' => 'desc'
                    ],
                    'deadline' => [
                        'value' => 'deadline',
                        'label' => 'По дедлайну',
                        'default_direction' => 'asc'
                    ],
                    'priority' => [
                        'value' => 'priority',
                        'label' => 'По приоритету',
                        'default_direction' => 'asc'
                    ],
                    'status' => [
                        'value' => 'status',
                        'label' => 'По статусу',
                        'default_direction' => 'asc'
                    ],
                    'title' => [
                        'value' => 'title',
                        'label' => 'По названию',
                        'default_direction' => 'asc'
                    ]
                ];
            },
            $this->cacheService->getTtl('static'),
            $tags
        );
    }

    /**
     * Получить все статические данные
     */
    public function getAllStaticData(): array
    {
        return [
            'statuses' => $this->getStatuses(),
            'priorities' => $this->getPriorities(),
            'categories' => $this->getCategories(),
            'tags' => $this->getTags(),
            'analytics_periods' => $this->getAnalyticsPeriods(),
            'sort_options' => $this->getSortOptions(),
            'config' => $this->getConfig(),
        ];
    }

    /**
     * Очистить кеш статических данных
     */
    public function clearStaticCache(): bool
    {
        $tags = $this->cacheService->getStaticTags();
        return $this->cacheService->flushTags($tags);
    }

    /**
     * Обновить конфигурацию
     */
    public function updateConfig(array $config): bool
    {
        $this->clearStaticCache();
        
        
        return true;
    }

    /**
     * Проверить, кешированы ли статические данные
     */
    public function isStaticDataCached(): bool
    {
        $keysToCheck = ['app_config', 'task_statuses', 'task_priorities'];
        
        $useTags = config('cache.default') !== 'array';
        $tags = $useTags ? $this->cacheService->getStaticTags() : [];
        
        foreach ($keysToCheck as $keyType) {
            $key = $this->keyGenerator->staticData($keyType);
            $data = $this->cacheService->get($key, $tags);
            
            if ($data !== null) {
                return true; // Найдены кешированные данные
            }
        }
        
        return false; // Никаких статических данных не найдено в кеше
    }

    /**
     * Прогреть кеш статических данных
     */
    public function warmStaticCache(): void
    {
        $this->getStatuses();
        $this->getPriorities();
        $this->getCategories();
        $this->getTags();
        $this->getAnalyticsPeriods();
        $this->getSortOptions();
        $this->getConfig();
    }
}
