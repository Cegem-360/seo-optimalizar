<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TrafficOverviewWidget extends ChartWidget
{
    protected ?string $heading = 'Traffic & Rankings Trend';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 2;

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 3 months',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        $days = (int) $this->filter;

        if (! $tenant) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        // Get rankings data grouped by date
        $rankings = \App\Models\Ranking::query()
            ->whereHas('keyword', function ($query) use ($tenant) {
                $query->where('project_id', $tenant->id);
            })
            ->whereBetween('checked_at', [$startDate, $endDate])
            ->selectRaw('DATE(checked_at) as date')
            ->selectRaw('AVG(position) as avg_position')
            ->selectRaw('COUNT(DISTINCT keyword_id) as keywords_tracked')
            ->selectRaw('COUNT(CASE WHEN position <= 10 THEN 1 END) as top10_count')
            ->selectRaw('SUM(JSON_EXTRACT(serp_features, "$.clicks")) as total_clicks')
            ->selectRaw('SUM(JSON_EXTRACT(serp_features, "$.impressions")) as total_impressions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $avgPositions = [];
        $top10Counts = [];
        $clicksData = [];
        $impressionsData = [];

        foreach ($rankings as $ranking) {
            $labels[] = Carbon::parse($ranking->date)->format('M d');
            $avgPositions[] = round($ranking->avg_position, 1);
            $top10Counts[] = $ranking->top10_count;
            $clicksData[] = $ranking->total_clicks ?? 0;
            $impressionsData[] = round(($ranking->total_impressions ?? 0) / 100); // Scale down for visibility
        }

        return [
            'datasets' => [
                [
                    'label' => 'Average Position',
                    'data' => $avgPositions,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Top 10 Rankings',
                    'data' => $top10Counts,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'yAxisID' => 'y1',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Clicks',
                    'data' => $clicksData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'yAxisID' => 'y1',
                    'tension' => 0.3,
                    'hidden' => true, // Hidden by default
                ],
                [
                    'label' => 'Impressions (÷100)',
                    'data' => $impressionsData,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                    'yAxisID' => 'y1',
                    'tension' => 0.3,
                    'hidden' => true, // Hidden by default
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Average Position',
                    ],
                    'reverse' => true, // Lower position is better
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Count',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
