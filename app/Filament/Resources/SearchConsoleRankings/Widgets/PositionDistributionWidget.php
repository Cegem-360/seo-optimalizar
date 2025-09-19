<?php

namespace App\Filament\Resources\SearchConsoleRankings\Widgets;

use App\Models\Project;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;

class PositionDistributionWidget extends ChartWidget
{
    public function getHeading(): string
    {
        return 'Position Distribution';
    }

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $builder = $project->searchConsoleRankings()
            ->where('date_to', '>=', Carbon::now()->subDays(30));

        $positionRanges = [
            '1-3' => (clone $builder)->whereBetween('position', [1, 3])->count(),
            '4-10' => (clone $builder)->whereBetween('position', [4, 10])->count(),
            '11-20' => (clone $builder)->whereBetween('position', [11, 20])->count(),
            '21-50' => (clone $builder)->whereBetween('position', [21, 50])->count(),
            '51-100' => (clone $builder)->whereBetween('position', [51, 100])->count(),
            '100+' => (clone $builder)->where('position', '>', 100)->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Keywords',
                    'data' => array_values($positionRanges),
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',   // green-500
                        'rgb(59, 130, 246)',  // blue-500
                        'rgb(251, 191, 36)',  // amber-400
                        'rgb(249, 115, 22)',  // orange-500
                        'rgb(239, 68, 68)',   // red-500
                        'rgb(107, 114, 128)', // gray-500
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_keys($positionRanges),
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
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}
