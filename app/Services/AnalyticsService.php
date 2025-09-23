<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnalyticsReport;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    public function storeAnalyticsReport(Project $project, array $analyticsData, ?Carbon $reportDate = null): AnalyticsReport
    {
        $reportDate ??= Carbon::yesterday();

        // Extract overview data
        $overview = $analyticsData['overview'] ?? [];

        // Create or update the analytics report
        $analyticsReport = AnalyticsReport::query()->updateOrCreate([
            'project_id' => $project->id,
            'report_date' => $reportDate->toDateString(),
        ], [
            'sessions' => $overview['sessions'] ?? 0,
            'active_users' => $overview['activeUsers'] ?? 0,
            'total_users' => $overview['totalUsers'] ?? 0,
            'new_users' => $overview['newUsers'] ?? 0,
            'bounce_rate' => $overview['bounceRate'] ?? 0,
            'average_session_duration' => $overview['averageSessionDuration'] ?? 0,
            'screen_page_views' => $overview['screenPageViews'] ?? 0,
            'conversions' => $overview['conversions'] ?? 0,
            'traffic_sources' => $analyticsData['traffic_sources'] ?? null,
            'top_pages' => $analyticsData['top_pages'] ?? null,
            'user_demographics' => $analyticsData['user_demographics'] ?? null,
            'device_data' => $analyticsData['device_data'] ?? null,
            'conversion_data' => $analyticsData['conversion_data'] ?? null,
            'real_time' => $analyticsData['real_time'] ?? null,
            'raw_data' => $analyticsData,
        ]);

        Log::info('Analytics report stored', [
            'project_id' => $project->id,
            'report_date' => $reportDate->toDateString(),
            'sessions' => $overview['sessions'] ?? 0,
            'active_users' => $overview['activeUsers'] ?? 0,
        ]);

        return $analyticsReport;
    }

    public function getLatestReport(Project $project): ?AnalyticsReport
    {
        return AnalyticsReport::query()->where('project_id', $project->id)
            ->orderByDesc('report_date')
            ->first();
    }

    public function getReportsForDateRange(Project $project, Carbon $startDate, Carbon $endDate): Collection
    {
        return AnalyticsReport::query()->where('project_id', $project->id)
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('report_date')
            ->get();
    }

    public function getMonthlyTrends(Project $project, int $months = 3): array
    {
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subMonths($months);

        $reports = $this->getReportsForDateRange($project, $startDate, $endDate);

        return [
            'sessions_trend' => $this->calculateTrend($reports, 'sessions'),
            'users_trend' => $this->calculateTrend($reports, 'active_users'),
            'bounce_rate_trend' => $this->calculateTrend($reports, 'bounce_rate'),
            'session_duration_trend' => $this->calculateTrend($reports, 'average_session_duration'),
            'conversions_trend' => $this->calculateTrend($reports, 'conversions'),
        ];
    }

    private function calculateTrend(Collection $reports, string $metric): array
    {
        $data = [];
        $previousValue = null;

        foreach ($reports as $report) {
            $currentValue = $report->{$metric};
            $change = $previousValue !== null ? $currentValue - $previousValue : 0;
            $changePercent = $previousValue > 0 ? (($change / $previousValue) * 100) : 0;

            $data[] = [
                'date' => $report->report_date->toDateString(),
                'value' => $currentValue,
                'change' => $change,
                'change_percent' => round($changePercent, 2),
            ];

            $previousValue = $currentValue;
        }

        return $data;
    }

    public function getTopPerformingPages(Project $project, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        $reports = $this->getReportsForDateRange($project, $startDate, Carbon::now());

        $allPages = [];

        foreach ($reports as $report) {
            if (! empty($report->top_pages)) {
                foreach ($report->top_pages as $page) {
                    $path = $page['pagePath'] ?? '';
                    if (! isset($allPages[$path])) {
                        $allPages[$path] = [
                            'pagePath' => $path,
                            'pageTitle' => $page['pageTitle'] ?? '',
                            'total_page_views' => 0,
                            'total_sessions' => 0,
                            'avg_bounce_rate' => 0,
                            'avg_session_duration' => 0,
                            'days_count' => 0,
                        ];
                    }

                    $allPages[$path]['total_page_views'] += $page['screenPageViews'] ?? 0;
                    $allPages[$path]['total_sessions'] += $page['sessions'] ?? 0;
                    $allPages[$path]['avg_bounce_rate'] += $page['bounceRate'] ?? 0;
                    $allPages[$path]['avg_session_duration'] += $page['averageSessionDuration'] ?? 0;
                    $allPages[$path]['days_count']++;
                }
            }
        }

        // Calculate averages and sort by page views
        foreach ($allPages as &$allPage) {
            if ($allPage['days_count'] > 0) {
                $allPage['avg_bounce_rate'] = round($allPage['avg_bounce_rate'] / $allPage['days_count'], 2);
                $allPage['avg_session_duration'] = round($allPage['avg_session_duration'] / $allPage['days_count'], 2);
            }
        }

        return collect($allPages)
            ->sortByDesc('total_page_views')
            ->take(20)
            ->values()
            ->toArray();
    }

    public function getTrafficSourceAnalysis(Project $project, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        $reports = $this->getReportsForDateRange($project, $startDate, Carbon::now());

        $sources = [];
        $totalSessions = 0;

        foreach ($reports as $report) {
            if (! empty($report->traffic_sources)) {
                foreach ($report->traffic_sources as $source) {
                    $channel = $source['sessionDefaultChannelGroup'] ?? 'Unknown';
                    if (! isset($sources[$channel])) {
                        $sources[$channel] = [
                            'channel' => $channel,
                            'total_sessions' => 0,
                            'total_users' => 0,
                            'avg_bounce_rate' => 0,
                            'total_conversions' => 0,
                            'days_count' => 0,
                        ];
                    }

                    $sessions = $source['sessions'] ?? 0;
                    $sources[$channel]['total_sessions'] += $sessions;
                    $sources[$channel]['total_users'] += $source['activeUsers'] ?? 0;
                    $sources[$channel]['avg_bounce_rate'] += $source['bounceRate'] ?? 0;
                    $sources[$channel]['total_conversions'] += $source['conversions'] ?? 0;
                    $sources[$channel]['days_count']++;
                    $totalSessions += $sessions;
                }
            }
        }

        // Calculate percentages and averages
        foreach ($sources as &$source) {
            if ($source['days_count'] > 0) {
                $source['avg_bounce_rate'] = round($source['avg_bounce_rate'] / $source['days_count'], 2);
            }

            $source['percentage'] = $totalSessions > 0 ? round(($source['total_sessions'] / $totalSessions) * 100, 2) : 0;
        }

        return collect($sources)
            ->sortByDesc('total_sessions')
            ->values()
            ->toArray();
    }
}
