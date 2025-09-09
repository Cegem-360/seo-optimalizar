<?php

namespace App\Filament\Resources\PageSpeedResults\Widgets;

use App\Models\PageSpeedResult;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Collection;

class PageSpeedTrendChart extends ChartWidget
{
    protected ?string $heading = 'Performance Trends (Last 30 Days)';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'mobile';

    protected function getData(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return ['datasets' => [], 'labels' => []];
        }

        /** @var Collection<int, PageSpeedResult> $results */
        $results = PageSpeedResult::forProject($project->id)
            ->strategy($this->filter)
            ->recent(30)
            ->orderBy('analyzed_at')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Performance',
                    'data' => $results->pluck('performance_score')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'SEO',
                    'data' => $results->pluck('seo_score')->toArray(),
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Accessibility',
                    'data' => $results->pluck('accessibility_score')->toArray(),
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Best Practices',
                    'data' => $results->pluck('best_practices_score')->toArray(),
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $results->map(fn (PageSpeedResult $pageSpeedResult) => $pageSpeedResult->analyzed_at ? $pageSpeedResult->analyzed_at->format('M d, H:i') : '')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'mobile' => 'Mobile',
            'desktop' => 'Desktop',
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
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'stepSize' => 20,
                    ],
                ],
            ],
        ];
    }
}
