<?php

namespace App\Services\Analytics\Charts;

/**
 * Круговая диаграмма для отображения распределения по категориям/тегам
 */
class PieChart implements ChartInterface
{
    private string $title;
    private array $colors;

    public function __construct(string $title = 'Распределение задач')
    {
        $this->title = $title;
        $this->colors = [
            '#667eea', '#764ba2', '#f093fb', '#f5576c',
            '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
            '#ffecd2', '#fcb69f', '#a8edea', '#fed6e3',
            '#ff9a9e', '#fecfef', '#ffeaa7', '#fab1a0'
        ];
    }

    public function getData(array $data): array
    {
        $labels = [];
        $values = [];
        $backgroundColors = [];

        foreach ($data as $index => $item) {
            // Поддерживаем разные форматы данных
            $labels[] = $item['priority'] ?? $item['status'] ?? $item['day'] ?? $item['time_period'] ?? $item['category'] ?? $item['tag'] ?? 'Без категории';
            $values[] = $item['count'] ?? 0;
            $backgroundColors[] = $this->colors[$index % count($this->colors)];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                    'hoverOffset' => 4
                ]
            ]
        ];
    }

    public function getConfig(): array
    {
        return [
            'type' => 'pie',
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => $this->title
                    ],
                    'legend' => [
                        'display' => true,
                        'position' => 'right'
                    ],
                    'tooltip' => [
                        'callbacks' => [
                            'label' => 'function(context) {
                                const label = context.label || "";
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ": " + value + " (" + percentage + "%)";
                            }'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function getType(): string
    {
        return 'pie';
    }
}