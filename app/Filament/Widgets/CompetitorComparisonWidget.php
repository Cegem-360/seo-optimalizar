<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CompetitorComparisonWidget extends ChartWidget
{
    protected ?string $heading = 'Position Distribution';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant) {
            return [
                'datasets' => [
                    [
                        'label' => 'Position Distribution',
                        'data' => [0, 0, 0, 0, 0],
                        'backgroundColor' => [
                            '#10b981',
                            '#3b82f6',
                            '#f59e0b',
                            '#ef4444',
                            '#9ca3af',
                        ],
                    ],
                ],
                'labels' => ['Top 3', 'Top 10', 'Top 20', 'Top 50', '50+'],
            ];
        }

        // Get latest rankings for each keyword
        $rankings = \App\Models\Ranking::query()
            ->whereHas('keyword', function ($query) use ($tenant) {
                $query->where('project_id', $tenant->id);
            })
            ->whereIn('rankings.id', function ($query) use ($tenant) {
                $query->select(DB::raw('MAX(rankings.id)'))
                    ->from('rankings')
                    ->join('keywords', 'rankings.keyword_id', '=', 'keywords.id')
                    ->where('keywords.project_id', $tenant->id)
                    ->groupBy('rankings.keyword_id');
            })
            ->get();

        $distribution = [
            'top3' => $rankings->where('position', '<=', 3)->count(),
            'top10' => $rankings->whereBetween('position', [4, 10])->count(),
            'top20' => $rankings->whereBetween('position', [11, 20])->count(),
            'top50' => $rankings->whereBetween('position', [21, 50])->count(),
            'beyond50' => $rankings->where('position', '>', 50)->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Keywords',
                    'data' => array_values($distribution),
                    'backgroundColor' => [
                        '#10b981', // green for top 3
                        '#3b82f6', // blue for top 10
                        '#f59e0b', // amber for top 20
                        '#ef4444', // red for top 50
                        '#9ca3af', // gray for 50+
                    ],
                ],
            ],
            'labels' => ['Top 3', 'Top 10', 'Top 20', 'Top 50', '50+'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "
                            function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed + ' keywords';
                                const percentage = ((context.parsed / context.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1);
                                label += ' (' + percentage + '%)';
                                return label;
                            }
                        ",
                    ],
                ],
            ],
        ];
    }
}
