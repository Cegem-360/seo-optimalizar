<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSpeedResults\Widgets;

use App\Models\PageSpeedResult;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PageSpeedOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        /** @var PageSpeedResult|null $latestResult */
        $latestResult = PageSpeedResult::query()
            ->select(['id', 'project_id', 'performance_score', 'analyzed_at'])
            ->forProject($project->id)
            ->latest('analyzed_at')
            ->first();

        $avgPerformance = PageSpeedResult::query()
            ->select(['performance_score'])
            ->forProject($project->id)
            ->recent(30)
            ->avg('performance_score');

        $avgSeo = PageSpeedResult::query()
            ->select(['seo_score'])
            ->forProject($project->id)
            ->recent(30)
            ->avg('seo_score');

        $avgAccessibility = PageSpeedResult::query()
            ->select(['accessibility_score'])
            ->forProject($project->id)
            ->recent(30)
            ->avg('accessibility_score');

        $totalScans = PageSpeedResult::query()
            ->select(['id'])
            ->forProject($project->id)
            ->count();

        return [
            Stat::make('Latest Performance Score', $latestResult ? $latestResult->performance_score . '/100' : 'No data')
                ->description($latestResult && $latestResult->analyzed_at ? 'Last scan: ' . Carbon::parse($latestResult->analyzed_at)->diffForHumans() : null)
                ->color($this->getScoreColor($latestResult?->performance_score))
                ->icon($this->getScoreIcon($latestResult?->performance_score)),

            Stat::make('Average Performance (30 days)', $avgPerformance ? round($avgPerformance) . '/100' : 'No data')
                ->description('Based on ' . PageSpeedResult::query()->forProject($project->id)->recent(30)->count() . ' scans')
                ->color($this->getScoreColor($avgPerformance))
                ->chart($this->getPerformanceTrend()),

            Stat::make('SEO Score', $avgSeo ? round($avgSeo) . '/100' : 'No data')
                ->description('30-day average')
                ->color($this->getScoreColor($avgSeo))
                ->icon('heroicon-o-magnifying-glass'),

            Stat::make('Accessibility Score', $avgAccessibility ? round($avgAccessibility) . '/100' : 'No data')
                ->description('30-day average')
                ->color($this->getScoreColor($avgAccessibility))
                ->icon('heroicon-o-eye'),

            Stat::make('Total Scans', $totalScans)
                ->description('All time')
                ->color('gray')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Latest LCP', $latestResult ? $latestResult->lcp_display : 'No data')
                ->description('Largest Contentful Paint')
                ->color($this->getMetricColor($latestResult?->lcp_score))
                ->icon('heroicon-o-clock'),
        ];
    }

    protected function getScoreColor(?float $score): string
    {
        return match (true) {
            $score === null => 'gray',
            $score >= 90 => 'success',
            $score >= 50 => 'warning',
            default => 'danger',
        };
    }

    protected function getMetricColor(?float $score): string
    {
        return match (true) {
            $score === null => 'gray',
            $score >= 0.9 => 'success',
            $score >= 0.5 => 'warning',
            default => 'danger',
        };
    }

    protected function getScoreIcon(?float $score): string
    {
        return match (true) {
            $score === null => 'heroicon-o-question-mark-circle',
            $score >= 90 => 'heroicon-o-check-circle',
            $score >= 50 => 'heroicon-o-exclamation-triangle',
            default => 'heroicon-o-x-circle',
        };
    }

    protected function getPerformanceTrend(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        $results = PageSpeedResult::query()
            ->forProject($project->id)
            ->recent(7)
            ->orderBy('analyzed_at')
            ->pluck('performance_score')
            ->toArray();

        return array_map('intval', $results);
    }
}
