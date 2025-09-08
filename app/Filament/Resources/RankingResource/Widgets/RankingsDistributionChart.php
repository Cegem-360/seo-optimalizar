<?php

namespace App\Filament\Resources\RankingResource\Widgets;

use App\Models\Ranking;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class RankingsDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Position Distribution';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    protected function getData(): array
    {
        $project = Filament::getTenant();
        
        $baseQuery = Ranking::whereHas('keyword', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })->recentlyChecked(30);
        
        // Position ranges
        $ranges = [
            '1-3' => (clone $baseQuery)->whereBetween('position', [1, 3])->count(),
            '4-10' => (clone $baseQuery)->whereBetween('position', [4, 10])->count(),
            '11-20' => (clone $baseQuery)->whereBetween('position', [11, 20])->count(),
            '21-50' => (clone $baseQuery)->whereBetween('position', [21, 50])->count(),
            '51-100' => (clone $baseQuery)->whereBetween('position', [51, 100])->count(),
            '100+' => (clone $baseQuery)->where('position', '>', 100)->count(),
        ];
        
        // Priority distribution
        $priorities = [
            'High' => (clone $baseQuery)->whereHas('keyword', function ($query) {
                $query->where('priority', 'high');
            })->count(),
            'Medium' => (clone $baseQuery)->whereHas('keyword', function ($query) {
                $query->where('priority', 'medium');
            })->count(),
            'Low' => (clone $baseQuery)->whereHas('keyword', function ($query) {
                $query->where('priority', 'low');
            })->count(),
        ];
        
        return [
            'datasets' => [
                [
                    'label' => 'Keywords by Position Range',
                    'data' => array_values($ranges),
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',   // Green for 1-3
                        'rgba(251, 146, 60, 0.8)',  // Orange for 4-10
                        'rgba(59, 130, 246, 0.8)',  // Blue for 11-20
                        'rgba(168, 85, 247, 0.8)',  // Purple for 21-50
                        'rgba(239, 68, 68, 0.8)',   // Red for 51-100
                        'rgba(107, 114, 128, 0.8)', // Gray for 100+
                    ],
                    'borderColor' => [
                        'rgba(34, 197, 94, 1)',
                        'rgba(251, 146, 60, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(168, 85, 247, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(107, 114, 128, 1)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => array_keys($ranges),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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
                        'label' => [
                            'formatter' => 'function(context) {
                                const label = context.label;
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ": " + value + " (" + percentage + "%)";
                            }'
                        ]
                    ]
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
