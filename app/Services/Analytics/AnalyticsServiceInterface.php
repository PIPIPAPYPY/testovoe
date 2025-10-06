<?php

namespace App\Services\Analytics;

/**
 * Интерфейс для сервиса аналитики задач
 * 
 * Определяет контракт для получения различных видов аналитических данных
 */
interface AnalyticsServiceInterface
{
    /**
     * Получить статистику создания задач за период
     * @param int $userId Идентификатор пользователя
     * @param string $period Период: 'day', 'week', 'month'
     * @return array
     */
    public function getTaskCreationStats(int $userId, string $period): array;

    /**
     * Получить статистику выполненных vs невыполненных задач
     * @param int $userId Идентификатор пользователя
     * @return array
     */
    public function getCompletionStats(int $userId): array;

    /**
     * Получить статистику по приоритетам
     * @param int $userId Идентификатор пользователя
     * @return array
     */
    public function getPriorityStats(int $userId): array;

    /**
     * Получить статистику активности по дням недели
     * @param int $userId Идентификатор пользователя
     * @return array
     */
    public function getWeeklyActivityStats(int $userId): array;



    /**
     * Получить общую статистику пользователя
     * @param int $userId Идентификатор пользователя
     * @return array
     */
    public function getOverallStats(int $userId): array;
}