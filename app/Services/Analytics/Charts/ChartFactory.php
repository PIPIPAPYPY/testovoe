<?php

namespace App\Services\Analytics\Charts;

use InvalidArgumentException;

/**
 * Фабрика для создания различных типов графиков
 * 
 * Реализует паттерн Factory для создания объектов графиков
 */
class ChartFactory
{
    /**
     * Создать график указанного типа
     * 
     * @param string $type Тип графика: 'line', 'pie', 'bar'
     * @param string $title Заголовок графика
     * @param string $xAxisLabel Подпись оси X (для line и bar)
     * @param string $yAxisLabel Подпись оси Y (для line и bar)
     * @return ChartInterface
     * @throws InvalidArgumentException
     */
    public static function create(
        string $type, 
        string $title = '', 
        string $xAxisLabel = '', 
        string $yAxisLabel = ''
    ): ChartInterface {
        return match (strtolower($type)) {
            'line' => new LineChart(
                $title ?: 'Динамика выполнения задач',
                $xAxisLabel ?: 'Период',
                $yAxisLabel ?: 'Количество задач'
            ),
            'pie' => new PieChart($title ?: 'Распределение задач'),
            'bar' => new BarChart(
                $title ?: 'Сравнение по категориям',
                $xAxisLabel ?: 'Категории',
                $yAxisLabel ?: 'Количество'
            ),
            default => throw new InvalidArgumentException("Неподдерживаемый тип графика: {$type}")
        };
    }

    /**
     * Получить список доступных типов графиков
     * 
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return [
            'line' => 'Линейный график',
            'pie' => 'Круговая диаграмма',
            'bar' => 'Столбчатая диаграмма'
        ];
    }
}