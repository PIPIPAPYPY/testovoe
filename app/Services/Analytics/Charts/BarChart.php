<?php

namespace App\Services\Analytics\Charts;

/**
 * Столбчатая диаграмма для отображения сравнительных данных
 */
class BarChart implements ChartInterface
{
    private string $title;
    private string $xAxisLabel;
    private string $yAxisLabel;

    public function __construct(string $title = 'Сравнение по категориям', string $xAxisLabel = 'Категории', string $yAxisLabel = 'Количество')
    {
        $this->title = $title;
        $this->xAxisLabel = $xAxisLabel;
        $this->yAxisLabel = $yAxisLabel;
    }

    public function getData(array $data): array
    {
        $labels = [];
        $values = [];

        foreach ($data as $item) {
            // Поддерживаем разные форматы данных
            $labels[] = $item['priority'] ?? $item['status'] ?? $item['day'] ?? $item['time_period'] ?? $item['period'] ?? '';
            $values[] = $item['count'] ?? 0;
        }

        // Генерируем цвета для каждого столбца
        $colors = [
            'rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)', 'rgba(255, 159, 64, 0.8)',
            'rgba(75, 192, 192, 0.8)', 'rgba(255, 99, 132, 0.8)', 'rgba(54, 162, 235, 0.8)',
            'rgba(255, 206, 86, 0.8)'
        ];

        $backgroundColors = [];
        for ($i = 0; $i < count($values); $i++) {
            $backgroundColors[] = $colors[$i % count($colors)];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Количество задач',
                    'data' => $values,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => '#667eea',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    public function getConfig(): array
    {
        return [
            'type' => 'bar',
            'options' => [
                'responsive' => true,
                'interaction' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => $this->title
                    ],
                    'legend' => [
                        'display' => true,
                        'position' => 'top'
                    ]
                ],
                'scales' => [
                    'x' => [
                        'display' => true,
                        'title' => [
                            'display' => true,
                            'text' => $this->xAxisLabel
                        ]
                    ],
                    'y' => [
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'title' => [
                            'display' => true,
                            'text' => $this->yAxisLabel
                        ],
                        'beginAtZero' => true
                    ]
                ]
            ]
        ];
    }

    public function getType(): string
    {
        return 'bar';
    }
}