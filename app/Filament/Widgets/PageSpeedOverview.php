<?php

namespace App\Filament\Widgets;

use App\Models\PageSpeedResult;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PageSpeedOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Project) {
            return [];
        }

        $recentResults = PageSpeedResult::query()->where('project_id', $tenant->id)
            ->where('analyzed_at', '>=', now()->subDays(30))
            ->get();

        $latestMobile = PageSpeedResult::query()->where('project_id', $tenant->id)
            ->where('strategy', 'mobile')
            ->latest('analyzed_at')
            ->first();

        $latestDesktop = PageSpeedResult::query()->where('project_id', $tenant->id)
            ->where('strategy', 'desktop')
            ->latest('analyzed_at')
            ->first();

        $avgPerformance = $recentResults->avg('performance_score');
        $avgAccessibility = $recentResults->avg('accessibility_score');
        $totalAnalyses = $recentResults->count();

        return [
            Stat::make('Latest Mobile Performance', $latestMobile?->performance_score ? $latestMobile->performance_score . '/100' : 'No data')
                ->description($latestMobile && $latestMobile->analyzed_at ? 'Analyzed ' . $latestMobile->analyzed_at->diffForHumans() : '')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color($this->getScoreColor($latestMobile?->performance_score))
                ->chart($this->getPerformanceTrend('mobile')),

            Stat::make('Latest Desktop Performance', $latestDesktop?->performance_score ? $latestDesktop->performance_score . '/100' : 'No data')
                ->description($latestDesktop && $latestDesktop->analyzed_at ? 'Analyzed ' . $latestDesktop->analyzed_at->diffForHumans() : '')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color($this->getScoreColor($latestDesktop?->performance_score))
                ->chart($this->getPerformanceTrend('desktop')),

            Stat::make('30-Day Avg Performance', $avgPerformance ? round($avgPerformance, 1) . '/100' : 'No data')
                ->description(sprintf('From %s analyses', $totalAnalyses))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($this->getScoreColor($avgPerformance)),

            Stat::make('30-Day Avg Accessibility', $avgAccessibility ? round($avgAccessibility, 1) . '/100' : 'No data')
                ->description('Accessibility score average')
                ->descriptionIcon('heroicon-m-eye')
                ->color($this->getScoreColor($avgAccessibility)),
        ];
    }

    private function getScoreColor(?float $score): string
    {
        if ($score === null) {
            return 'gray';
        }

        return match (true) {
            $score >= 90 => 'success',
            $score >= 50 => 'warning',
            default => 'danger',
        };
    }

    private function getPerformanceTrend(string $strategy): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Project) {
            return [];
        }

        return PageSpeedResult::query()->where('project_id', $tenant->id)
            ->where('strategy', $strategy)
            ->where('analyzed_at', '>=', now()->subDays(7))
            ->orderBy('analyzed_at')
            ->pluck('performance_score')
            ->toArray();
    }
}
