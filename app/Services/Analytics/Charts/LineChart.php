<?php

namespace App\Services\Analytics\Charts;

/**
 * Линейный график для отображения динамики выполнения задач
 */
class LineChart implements ChartInterface
{
    private string $title;
    private string $xAxisLabel;
    private string $yAxisLabel;

    public function __construct(string $title = 'Динамика выполнения задач', string $xAxisLabel = 'Период', string $yAxisLabel = 'Количество задач')
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
            $labels[] = $item['period'] ?? $item['date'] ?? '';
            $values[] = $item['count'] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Количество задач',
                    'data' => $values,
                    'borderColor' => '#667eea',
                    'backgroundColor' => 'rgba(102, 126, 234, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                    'pointBackgroundColor' => '#667eea',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4
                ]
            ]
        ];
    }

    public function getConfig(): array
    {
        return [
            'type' => 'line',
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
        return 'line';
    }
}