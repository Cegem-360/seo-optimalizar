<?php

namespace App\Filament\Widgets;

use App\Models\Keyword;
use App\Models\Ranking;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SeoStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof \App\Models\Project) {
            return [];
        }

        $totalKeywords = Keyword::query()->where('project_id', $tenant->id)->count();
        $totalRankings = Ranking::query()->whereHas('keyword', function ($query) use ($tenant): void {
            $query->where('project_id', $tenant->id);
        })->count();

        $avgPosition = Ranking::query()->whereHas('keyword', function ($query) use ($tenant): void {
            $query->where('project_id', $tenant->id);
        })->avg('position');

        $topPositions = Ranking::query()->whereHas('keyword', function ($query) use ($tenant): void {
            $query->where('project_id', $tenant->id);
        })->where('position', '<=', 10)->count();

        return [
            Stat::make('Total Keywords', $totalKeywords)
                ->description('Keywords being tracked')
                ->descriptionIcon('heroicon-m-key')
                ->color('primary'),

            Stat::make('Total Rankings', $totalRankings)
                ->description('Ranking records collected')
                ->descriptionIcon('heroicon-m-chart-bar-square')
                ->color('info'),

            Stat::make('Average Position', number_format($avgPosition ?? 0, 1))
                ->description('Current average ranking')
                ->descriptionIcon(Heroicon::ArrowTrendingUp)
                ->color($avgPosition && $avgPosition <= 20 ? 'success' : 'warning'),

            Stat::make('Top 10 Rankings', $topPositions)
                ->description('Keywords ranking in top 10')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),
        ];
    }
}
