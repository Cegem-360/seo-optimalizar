<?php

namespace App\Filament\Resources\SearchConsoleRankings\Widgets;

use App\Models\Project;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class ClicksPerformanceWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return 'Clicks Performance (Last 30 Days)';
    }

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = [];
        $clicksData = [];
        $impressionsData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $labels[] = $date->format('M j');

            $dailyClicks = $project->searchConsoleRankings()
                ->whereDate('date_from', '<=', $dateString)
                ->whereDate('date_to', '>=', $dateString)
                ->sum('clicks');

            $dailyImpressions = $project->searchConsoleRankings()
                ->whereDate('date_from', '<=', $dateString)
                ->whereDate('date_to', '>=', $dateString)
                ->sum('impressions');

            $clicksData[] = $dailyClicks;
            $impressionsData[] = $dailyImpressions;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Clicks',
                    'data' => $clicksData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Impressions',
                    'data' => $impressionsData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
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

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Clicks',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Impressions',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
