<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsReport;
use App\Models\Project;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (!$tenant instanceof Project) {
            return [];
        }

        // Get latest analytics report for the project
        $latestReport = AnalyticsReport::where('project_id', $tenant->id)
            ->orderByDesc('report_date')
            ->first();

        if (!$latestReport) {
            return [
                Stat::make('Analytics', 'No data available')
                    ->description('Start collecting analytics data')
                    ->color('gray'),
            ];
        }

        // Get previous report for comparison
        $previousReport = AnalyticsReport::where('project_id', $tenant->id)
            ->where('report_date', '<', $latestReport->report_date)
            ->orderByDesc('report_date')
            ->first();

        return [
            Stat::make('Sessions', number_format($latestReport->sessions))
                ->description($this->getChangeDescription($latestReport->sessions, $previousReport?->sessions))
                ->descriptionIcon($this->getChangeIcon($latestReport->sessions, $previousReport?->sessions))
                ->color($this->getChangeColor($latestReport->sessions, $previousReport?->sessions))
                ->chart($this->getChartData($tenant, 'sessions')),

            Stat::make('Active Users', number_format($latestReport->active_users))
                ->description($this->getChangeDescription($latestReport->active_users, $previousReport?->active_users))
                ->descriptionIcon($this->getChangeIcon($latestReport->active_users, $previousReport?->active_users))
                ->color($this->getChangeColor($latestReport->active_users, $previousReport?->active_users))
                ->chart($this->getChartData($tenant, 'active_users')),

            Stat::make('Bounce Rate', number_format($latestReport->bounce_rate, 1) . '%')
                ->description($this->getChangeDescription($latestReport->bounce_rate, $previousReport?->bounce_rate, true))
                ->descriptionIcon($this->getChangeIcon($latestReport->bounce_rate, $previousReport?->bounce_rate, true))
                ->color($this->getChangeColor($latestReport->bounce_rate, $previousReport?->bounce_rate, true))
                ->chart($this->getChartData($tenant, 'bounce_rate')),

            Stat::make('Conversions', number_format($latestReport->conversions))
                ->description($this->getChangeDescription($latestReport->conversions, $previousReport?->conversions))
                ->descriptionIcon($this->getChangeIcon($latestReport->conversions, $previousReport?->conversions))
                ->color($this->getChangeColor($latestReport->conversions, $previousReport?->conversions))
                ->chart($this->getChartData($tenant, 'conversions')),
        ];
    }

    private function getChangeDescription(?float $current, ?float $previous, bool $reverse = false): string
    {
        if ($previous === null || $previous == 0) {
            return 'No previous data';
        }

        $change = $current - $previous;
        $changePercent = abs(($change / $previous) * 100);

        if ($change > 0) {
            return ($reverse ? 'Increased by ' : 'Increased by ') . number_format($changePercent, 1) . '%';
        } elseif ($change < 0) {
            return ($reverse ? 'Decreased by ' : 'Decreased by ') . number_format($changePercent, 1) . '%';
        }

        return 'No change';
    }

    private function getChangeIcon(?float $current, ?float $previous, bool $reverse = false): string
    {
        if ($previous === null || $previous == 0) {
            return 'heroicon-m-minus';
        }

        $change = $current - $previous;

        if ($change > 0) {
            return $reverse ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up';
        } elseif ($change < 0) {
            return $reverse ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        }

        return 'heroicon-m-minus';
    }

    private function getChangeColor(?float $current, ?float $previous, bool $reverse = false): string
    {
        if ($previous === null || $previous == 0) {
            return 'gray';
        }

        $change = $current - $previous;

        if ($change > 0) {
            return $reverse ? 'danger' : 'success';
        } elseif ($change < 0) {
            return $reverse ? 'success' : 'danger';
        }

        return 'gray';
    }

    private function getChartData(Project $project, string $metric): array
    {
        $reports = AnalyticsReport::where('project_id', $project->id)
            ->orderBy('report_date')
            ->limit(7)
            ->get();

        return $reports->pluck($metric)->toArray();
    }

    public function getPollingInterval(): ?string
    {
        return '30s';
    }

    protected function getColumns(): int
    {
        return 4;
    }
}