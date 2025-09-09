<?php

namespace App\Filament\Resources\RankingResource\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class RankingsTrendChart extends ChartWidget
{
    protected ?string $heading = 'Rankings Performance Over Time';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30';

    protected function getData(): array
    {
        $project = Filament::getTenant();
        $days = (int) $this->filter;
        $labels = [];
        $topThreeData = [];
        $topTenData = [];
        $avgPositionData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');

            $dayQuery = \App\Models\Ranking::query()->whereHas('keyword', function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            })->whereBetween('checked_at', [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
            ]);

            $totalForDay = (clone $dayQuery)->count();
            $topThreeForDay = (clone $dayQuery)->topThree()->count();
            $topTenForDay = (clone $dayQuery)->topTen()->count();
            $avgPositionForDay = (clone $dayQuery)->avg('position');

            $topThreeData[] = $totalForDay > 0 ? round(($topThreeForDay / $totalForDay) * 100, 1) : 0;
            $topTenData[] = $totalForDay > 0 ? round(($topTenForDay / $totalForDay) * 100, 1) : 0;
            $avgPositionData[] = $avgPositionForDay ? round($avgPositionForDay, 1) : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Top 3 (%)',
                    'data' => $topThreeData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Top 10 (%)',
                    'data' => $topTenData,
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'tension' => 0.3,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Avg Position',
                    'data' => $avgPositionData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Percentage (%)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => false,
                    'reverse' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Average Position',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
