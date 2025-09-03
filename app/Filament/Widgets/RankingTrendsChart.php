<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class RankingTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Average Position Trend (Last 30 Days)';

    protected function getData(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M j');

            $avgPosition = \App\Models\Ranking::query()->whereHas('keyword', function ($query) use ($tenant): void {
                $query->where('project_id', $tenant->id);
            })
                ->whereDate('checked_at', $date)
                ->avg('position');

            $data[] = $avgPosition ? round($avgPosition, 1) : null;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Average Position',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
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
            'scales' => [
                'y' => [
                    'reverse' => true,
                    'beginAtZero' => false,
                    'title' => [
                        'display' => true,
                        'text' => 'Position',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
