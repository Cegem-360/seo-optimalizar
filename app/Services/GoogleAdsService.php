<?php

namespace App\Services;

use App\Models\GoogleAdsReport;
use App\Models\Project;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleAdsService
{
    public function storeGoogleAdsReport(Project $project, array $googleAdsData, ?Carbon $reportDate = null): GoogleAdsReport
    {
        $reportDate = $reportDate ?? Carbon::today();

        // Create or update the Google Ads report
        $report = GoogleAdsReport::updateOrCreate(
            [
                'project_id' => $project->id,
                'report_date' => $reportDate->toDateString(),
            ],
            [
                'metadata' => $googleAdsData['metadata'] ?? null,
                'keyword_data' => $googleAdsData['keyword_data'] ?? null,
                'historical_metrics' => $googleAdsData['historical_metrics'] ?? null,
                'bulk_results' => $googleAdsData['bulk_results'] ?? null,
                'statistics' => $googleAdsData['statistics'] ?? null,
                'raw_data' => $googleAdsData,
            ]
        );

        $keywordCount = count($googleAdsData['keyword_data'] ?? []);
        $successfulFetches = $googleAdsData['statistics']['successful_fetches'] ?? 0;

        Log::info('Google Ads report stored', [
            'project_id' => $project->id,
            'report_date' => $reportDate->toDateString(),
            'keyword_count' => $keywordCount,
            'successful_fetches' => $successfulFetches,
        ]);

        return $report;
    }

    public function getLatestReport(Project $project): ?GoogleAdsReport
    {
        return GoogleAdsReport::where('project_id', $project->id)
            ->orderByDesc('report_date')
            ->first();
    }

    public function getReportsForDateRange(Project $project, Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return GoogleAdsReport::where('project_id', $project->id)
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('report_date')
            ->get();
    }

    public function getKeywordPerformanceAnalysis(Project $project, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        $reports = $this->getReportsForDateRange($project, $startDate, Carbon::now());

        $keywordAnalysis = [];

        foreach ($reports as $report) {
            if (!empty($report->keyword_data)) {
                foreach ($report->keyword_data as $keyword) {
                    $keywordText = $keyword['keyword'] ?? '';
                    if (!isset($keywordAnalysis[$keywordText])) {
                        $keywordAnalysis[$keywordText] = [
                            'keyword' => $keywordText,
                            'avg_search_volume' => 0,
                            'avg_difficulty' => 0,
                            'avg_competition' => 0,
                            'min_cpc' => null,
                            'max_cpc' => null,
                            'reports_count' => 0,
                        ];
                    }

                    $analysis = &$keywordAnalysis[$keywordText];
                    $analysis['avg_search_volume'] += $keyword['search_volume'] ?? 0;
                    $analysis['avg_difficulty'] += $keyword['difficulty'] ?? 0;
                    $analysis['avg_competition'] += $keyword['competition'] ?? 0;

                    $cpc = $keyword['suggested_bid'] ?? 0;
                    if ($cpc > 0) {
                        $analysis['min_cpc'] = $analysis['min_cpc'] === null ? $cpc : min($analysis['min_cpc'], $cpc);
                        $analysis['max_cpc'] = $analysis['max_cpc'] === null ? $cpc : max($analysis['max_cpc'], $cpc);
                    }

                    $analysis['reports_count']++;
                }
            }
        }

        // Calculate averages
        foreach ($keywordAnalysis as &$analysis) {
            if ($analysis['reports_count'] > 0) {
                $analysis['avg_search_volume'] = round($analysis['avg_search_volume'] / $analysis['reports_count']);
                $analysis['avg_difficulty'] = round($analysis['avg_difficulty'] / $analysis['reports_count'], 2);
                $analysis['avg_competition'] = round($analysis['avg_competition'] / $analysis['reports_count'], 2);
            }
        }

        return collect($keywordAnalysis)
            ->sortByDesc('avg_search_volume')
            ->take(50)
            ->values()
            ->toArray();
    }

    public function getHistoricalTrends(Project $project): array
    {
        $latestReport = $this->getLatestReport($project);

        if (!$latestReport || empty($latestReport->historical_metrics)) {
            return [];
        }

        $trends = [];

        foreach ($latestReport->historical_metrics as $keyword => $metrics) {
            if (!empty($metrics['monthly_data'])) {
                $monthlyData = [];
                foreach ($metrics['monthly_data'] as $month => $data) {
                    $monthlyData[] = [
                        'month' => $month,
                        'search_volume' => $data['search_volume'] ?? 0,
                        'competition' => $data['competition'] ?? 0,
                    ];
                }

                $trends[$keyword] = [
                    'keyword' => $keyword,
                    'monthly_data' => $monthlyData,
                    'avg_search_volume' => collect($monthlyData)->avg('search_volume'),
                    'trend_direction' => $this->calculateTrendDirection($monthlyData),
                ];
            }
        }

        return collect($trends)
            ->sortByDesc('avg_search_volume')
            ->take(20)
            ->values()
            ->toArray();
    }

    private function calculateTrendDirection(array $monthlyData): string
    {
        if (count($monthlyData) < 2) {
            return 'stable';
        }

        $recent = array_slice($monthlyData, -3); // Last 3 months
        $earlier = array_slice($monthlyData, 0, 3); // First 3 months

        $recentAvg = collect($recent)->avg('search_volume');
        $earlierAvg = collect($earlier)->avg('search_volume');

        if ($recentAvg > $earlierAvg * 1.1) {
            return 'up';
        } elseif ($recentAvg < $earlierAvg * 0.9) {
            return 'down';
        }

        return 'stable';
    }

    public function getKeywordStatistics(Project $project): array
    {
        $latestReport = $this->getLatestReport($project);

        if (!$latestReport) {
            return [
                'total_keywords' => 0,
                'avg_search_volume' => 0,
                'avg_difficulty' => 0,
                'high_volume_keywords' => 0,
                'low_competition_keywords' => 0,
            ];
        }

        $keywordData = $latestReport->keyword_data ?? [];
        $totalKeywords = count($keywordData);

        if ($totalKeywords === 0) {
            return [
                'total_keywords' => 0,
                'avg_search_volume' => 0,
                'avg_difficulty' => 0,
                'high_volume_keywords' => 0,
                'low_competition_keywords' => 0,
            ];
        }

        $totalSearchVolume = 0;
        $totalDifficulty = 0;
        $highVolumeCount = 0;
        $lowCompetitionCount = 0;

        foreach ($keywordData as $keyword) {
            $searchVolume = $keyword['search_volume'] ?? 0;
            $difficulty = $keyword['difficulty'] ?? 0;
            $competition = $keyword['competition'] ?? 0;

            $totalSearchVolume += $searchVolume;
            $totalDifficulty += $difficulty;

            if ($searchVolume >= 1000) {
                $highVolumeCount++;
            }

            if ($competition <= 0.3) {
                $lowCompetitionCount++;
            }
        }

        return [
            'total_keywords' => $totalKeywords,
            'avg_search_volume' => round($totalSearchVolume / $totalKeywords),
            'avg_difficulty' => round($totalDifficulty / $totalKeywords, 2),
            'high_volume_keywords' => $highVolumeCount,
            'low_competition_keywords' => $lowCompetitionCount,
        ];
    }
}