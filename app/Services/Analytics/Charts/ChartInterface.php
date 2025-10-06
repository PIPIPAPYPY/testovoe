<?php

namespace App\Services\Analytics\Charts;

/**
 * Интерфейс для графиков аналитики
 * 
 * Определяет контракт для различных типов графиков
 */
interface ChartInterface
{
    /**
     * Получить данные для графика
     * @param array $data Исходные данные
     * @return array Данные в формате, подходящем для графика
     */
    public function getData(array $data): array;

    /**
     * Получить конфигурацию графика
     * @return array Конфигурация для фронтенда
     */
    public function getConfig(): array;

    /**
     * Получить тип графика
     * @return string Тип графика
     */
    public function getType(): string;
}