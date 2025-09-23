<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleAnalytics4StatsWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Project) {
            return [];
        }

        try {
            $apiManager = ApiServiceManager::forProject($tenant);

            if (! $apiManager->hasService('google_analytics_4')) {
                return [
                    Stat::make('GA4 Status', 'Not Configured')
                        ->description('Configure Google Analytics 4 in API Settings')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning'),
                ];
            }

            $ga4Service = $apiManager->getGoogleAnalytics4();

            // Get data for the last 30 days vs previous 30 days
            $currentPeriodEnd = Carbon::now()->subDays(1);
            $currentPeriodStart = Carbon::now()->subDays(30);
            $previousPeriodEnd = Carbon::now()->subDays(31);
            $previousPeriodStart = Carbon::now()->subDays(60);

            $currentData = $ga4Service->getAudienceOverview($currentPeriodStart, $currentPeriodEnd);
            $previousData = $ga4Service->getAudienceOverview($previousPeriodStart, $previousPeriodEnd);

            if ($currentData->isEmpty()) {
                return [
                    Stat::make('GA4 Data', 'No Data Available')
                        ->description('No analytics data found')
                        ->icon('heroicon-o-chart-bar')
                        ->color('gray'),
                ];
            }

            // Aggregate current period data
            $currentSessions = $currentData->sum('sessions');
            $currentUsers = $currentData->sum('activeUsers');
            $currentPageViews = $currentData->sum('screenPageViews');
            $currentBounceRate = $currentData->isEmpty() ? 0 : $currentData->avg('bounceRate');

            // Aggregate previous period data
            $previousSessions = $previousData->sum('sessions');
            $previousUsers = $previousData->sum('activeUsers');
            $previousPageViews = $previousData->sum('screenPageViews');

            // Calculate percentage changes
            $sessionsChange = $this->calculatePercentageChange($currentSessions, $previousSessions);
            $usersChange = $this->calculatePercentageChange($currentUsers, $previousUsers);
            $pageViewsChange = $this->calculatePercentageChange($currentPageViews, $previousPageViews);

            return [
                Stat::make('Total Sessions', number_format($currentSessions))
                    ->description($this->formatChange($sessionsChange) . ' from last month')
                    ->icon('heroicon-o-users')
                    ->color($sessionsChange >= 0 ? 'success' : 'danger'),

                Stat::make('Active Users', number_format($currentUsers))
                    ->description($this->formatChange($usersChange) . ' from last month')
                    ->icon('heroicon-o-user-group')
                    ->color($usersChange >= 0 ? 'success' : 'danger'),

                Stat::make('Page Views', number_format($currentPageViews))
                    ->description($this->formatChange($pageViewsChange) . ' from last month')
                    ->icon('heroicon-o-eye')
                    ->color($pageViewsChange >= 0 ? 'success' : 'danger'),

                Stat::make('Bounce Rate', number_format($currentBounceRate, 1) . '%')
                    ->description('Average for the period')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color($currentBounceRate < 50 ? 'success' : ($currentBounceRate < 70 ? 'warning' : 'danger')),
            ];
        } catch (Exception $exception) {
            Log::error('GA4 Stats Widget error', [
                'project_id' => $tenant->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                Stat::make('GA4 Error', 'Connection Failed')
                    ->description('Unable to load analytics data')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }

    private function calculatePercentageChange(int|float $current, int|float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function formatChange(float $change): string
    {
        $formatted = number_format(abs($change), 1);
        if ($change > 0) {
            return sprintf('+%s%%', $formatted);
        }

        if ($change < 0) {
            return sprintf('-%s%%', $formatted);
        }

        return '0%';
    }
}
