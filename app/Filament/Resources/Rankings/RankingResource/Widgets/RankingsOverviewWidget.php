<?php

namespace App\Filament\Resources\Rankings\RankingResource\Widgets;

use App\Models\Project;
use App\Models\Ranking;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class RankingsOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        // Base query for current project
        $baseQuery = Ranking::query()->whereHas('keyword', function ($query) use ($project): void {
            $query->where('project_id', $project->id);
        })->recentlyChecked(30);

        // Get date range for display
        $earliestRanking = (clone $baseQuery)->oldest('checked_at')->first();
        $latestRanking = (clone $baseQuery)->latest('checked_at')->first();

        $dateRangeDescription = 'No data available';
        if ($earliestRanking && $latestRanking) {
            $startDate = Carbon::parse($earliestRanking->checked_at)->format('M d, Y');
            $endDate = Carbon::parse($latestRanking->checked_at)->format('M d, Y');

            if ($startDate === $endDate) {
                $dateRangeDescription = "Data from: {$startDate}";
            } else {
                $dateRangeDescription = "Data from: {$startDate} - {$endDate}";
            }
        }

        $totalRankings = (clone $baseQuery)->count();
        $topThree = (clone $baseQuery)->topThree()->count();
        $topTen = (clone $baseQuery)->topTen()->count();
        $improved = (clone $baseQuery)->improved()->count();
        $declined = (clone $baseQuery)->declined()->count();
        $featuredSnippets = (clone $baseQuery)->where('featured_snippet', true)->count();

        $avgPosition = (clone $baseQuery)->avg('position');

        return [
            Stat::make('Total Rankings', $totalRankings)
                ->description($dateRangeDescription)
                ->color('primary')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Top 3 Positions', $topThree)
                ->description($totalRankings > 0 ? round(($topThree / $totalRankings) * 100) . '% of total' : 'No data')
                ->color('success')
                ->icon('heroicon-o-trophy'),

            Stat::make('Top 10 Positions', $topTen)
                ->description($totalRankings > 0 ? round(($topTen / $totalRankings) * 100) . '% of total' : 'No data')
                ->color('warning')
                ->icon('heroicon-o-star'),

            Stat::make('Average Position', $avgPosition ? round($avgPosition, 1) : 'No data')
                ->description('Across all tracked keywords')
                ->color($this->getPositionColor($avgPosition))
                ->icon('heroicon-o-calculator'),

            Stat::make('Improved Rankings', $improved)
                ->description('Keywords that moved up')
                ->color('success')
                ->icon('heroicon-o-arrow-trending-up')
                ->chart($this->getImprovementTrend()),

            Stat::make('Declined Rankings', $declined)
                ->description('Keywords that moved down')
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('Featured Snippets', $featuredSnippets)
                ->description($totalRankings > 0 ? round(($featuredSnippets / $totalRankings) * 100) . '% of rankings' : 'No data')
                ->color('warning')
                ->icon('heroicon-o-sparkles'),

            Stat::make('Keywords Performance', $this->getPerformanceGrade($topThree, $topTen, $totalRankings))
                ->description('Overall ranking performance')
                ->color($this->getPerformanceColor($topThree, $topTen, $totalRankings))
                ->icon('heroicon-o-academic-cap'),
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

    protected function getPerformanceGrade(int $topThree, int $topTen, int $total): string
    {
        if ($total === 0) {
            return 'No Data';
        }

        $topThreePercent = ($topThree / $total) * 100;
        $topTenPercent = ($topTen / $total) * 100;

        return match (true) {
            $topThreePercent >= 50 => 'Excellent',
            $topThreePercent >= 25 => 'Very Good',
            $topTenPercent >= 60 => 'Good',
            $topTenPercent >= 30 => 'Average',
            default => 'Needs Work',
        };
    }

    protected function getPerformanceColor(int $topThree, int $topTen, int $total): string
    {
        if ($total === 0) {
            return 'gray';
        }

        $topThreePercent = ($topThree / $total) * 100;
        $topTenPercent = ($topTen / $total) * 100;

        return match (true) {
            $topThreePercent >= 50 => 'success',
            $topThreePercent >= 25 => 'warning',
            $topTenPercent >= 60 => 'info',
            default => 'danger',
        };
    }

    protected function getImprovementTrend(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        // Get last 7 days of improvement data
        $improvements = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Ranking::query()->whereHas('keyword', function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            })
                ->improved()
                ->whereBetween('checked_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
                ->count();

            $improvements[] = $count;
        }

        return $improvements;
    }
}
