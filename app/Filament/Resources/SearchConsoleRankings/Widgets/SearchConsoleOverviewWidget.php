<?php

declare(strict_types=1);

namespace App\Filament\Resources\SearchConsoleRankings\Widgets;

use App\Models\Project;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SearchConsoleOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        // Base query for current project
        $builder = $project->searchConsoleRankings()
            ->where('date_to', '>=', Carbon::now()->subDays(30));

        // Get statistics
        $totalQueries = (clone $builder)->distinct('query')->count('query');
        $totalClicks = (clone $builder)->sum('clicks');
        $totalImpressions = (clone $builder)->sum('impressions');
        $avgPosition = (clone $builder)->avg('position');
        $avgCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) : 0;

        // Position breakdowns
        $topThree = (clone $builder)->where('position', '<=', 3)->count();
        (clone $builder)->where('position', '<=', 10)->count();
        $firstPage = (clone $builder)->where('position', '<=', 10)->count();

        // Trends
        $improved = (clone $builder)->improved()->count();
        $declined = (clone $builder)->declined()->count();

        // Date range info
        $earliestDate = (clone $builder)->min('date_from');
        $latestDate = (clone $builder)->max('date_to');
        $dateRange = 'Last 30 days';

        if ($earliestDate && $latestDate) {
            $start = Carbon::parse($earliestDate)->format('M d');
            $end = Carbon::parse($latestDate)->format('M d, Y');
            $dateRange = sprintf('%s - %s', $start, $end);
        }

        return [
            Stat::make('Total Queries', number_format($totalQueries))
                ->description($dateRange)
                ->color('primary')
                ->icon('heroicon-o-magnifying-glass'),

            Stat::make('Total Clicks', number_format($totalClicks))
                ->description('From Search Console')
                ->color('success')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->chart($this->getClicksTrend()),

            Stat::make('Total Impressions', number_format($totalImpressions))
                ->description('From Search Console')
                ->color('warning')
                ->icon('heroicon-o-eye'),

            Stat::make('Average Position', $avgPosition ? number_format($avgPosition, 1) : 'No data')
                ->description('Across all queries')
                ->color($this->getPositionColor($avgPosition))
                ->icon('heroicon-o-chart-bar-square'),

            Stat::make('Average CTR', number_format($avgCtr * 100, 2) . '%')
                ->description('Click-through rate')
                ->color($this->getCtrColor($avgCtr))
                ->icon('heroicon-o-cursor-arrow-ripple'),

            Stat::make('First Page Rankings', $firstPage)
                ->description($totalQueries > 0 ? round(($firstPage / $totalQueries) * 100) . '% of queries' : 'No data')
                ->color('info')
                ->icon('heroicon-o-document-text'),

            Stat::make('Top 3 Positions', $topThree)
                ->description($totalQueries > 0 ? round(($topThree / $totalQueries) * 100) . '% of queries' : 'No data')
                ->color('success')
                ->icon('heroicon-o-trophy'),

            Stat::make('Position Changes', $improved . ' ↑ / ' . $declined . ' ↓')
                ->description('Improved vs Declined')
                ->color($improved >= $declined ? 'success' : 'danger')
                ->icon($improved >= $declined ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'),
        ];
    }

    protected function getPositionColor(?float $position): string
    {
        return match (true) {
            $position === null => 'gray',
            $position <= 3 => 'success',
            $position <= 10 => 'warning',
            $position <= 20 => 'info',
            default => 'danger',
        };
    }

    protected function getCtrColor(float $ctr): string
    {
        return match (true) {
            $ctr >= 0.10 => 'success',
            $ctr >= 0.05 => 'warning',
            $ctr >= 0.02 => 'info',
            default => 'gray',
        };
    }

    protected function getClicksTrend(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        // Get last 7 days of clicks data
        $clicks = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $count = $project->searchConsoleRankings()
                ->whereDate('date_from', '<=', $date)
                ->whereDate('date_to', '>=', $date)
                ->sum('clicks');

            $clicks[] = $count;
        }

        return $clicks;
    }
}
