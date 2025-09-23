<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\SearchConsoleRanking;
use Carbon\Carbon;
use Exception;
use Google\Client;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GoogleSearchConsoleService extends BaseApiService
{
    protected string $serviceName = 'google_search_console';

    private ?Client $client = null;

    private ?SearchConsole $service = null;

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        // This method is overridden - we use Google API client instead
    }

    private function getGoogleClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $serviceAccountJson = $this->getCredential('service_account_json');

        if (! $serviceAccountJson || empty($serviceAccountJson['private_key']) || empty($serviceAccountJson['client_email'])) {
            throw new Exception('Missing Google Search Console service account credentials');
        }

        $this->client = new Client();
        $this->client->setAuthConfig($serviceAccountJson);
        $this->client->addScope(SearchConsole::WEBMASTERS_READONLY);
        $this->client->useApplicationDefaultCredentials();

        return $this->client;
    }

    private function getSearchConsoleService(): SearchConsole
    {
        if ($this->service !== null) {
            return $this->service;
        }

        $client = $this->getGoogleClient();
        $this->service = new SearchConsole($client);

        return $this->service;
    }

    public function testConnection(): bool
    {
        try {
            $service = $this->getSearchConsoleService();
            $service->sites->listSites();

            return true;
        } catch (Exception $exception) {
            Log::error('Google Search Console - Connection test failed', [
                'project_id' => $this->project->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function getSites(): Collection
    {
        try {
            $service = $this->getSearchConsoleService();
            $sitesListResponse = $service->sites->listSites();
            $sites = $sitesListResponse->getSiteEntry() ?? [];

            return new Collection($sites);
        } catch (Exception $exception) {
            Log::error('Google Search Console - Failed to get sites', [
                'project_id' => $this->project->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function getSearchAnalytics(array $dimensions = ['query'], ?Carbon $startDate = null, ?Carbon $endDate = null, int $rowLimit = 1000): Collection
    {
        $startDate ??= now()->subDays(7);
        $endDate ??= now()->subDays(1);

        $siteUrl = $this->project->url;
        $propertyUrl = $this->getCredential('property_url') ?? $siteUrl;

        try {
            $service = $this->getSearchConsoleService();

            $request = new SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate->format('Y-m-d'));
            $request->setEndDate($endDate->format('Y-m-d'));
            $request->setDimensions($dimensions);
            $request->setRowLimit($rowLimit);
            $request->setStartRow(0);

            Log::info('Google Search Console - Fetching search analytics', [
                'project_id' => $this->project->id,
                'site_url' => $propertyUrl,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'dimensions' => $dimensions,
                'row_limit' => $rowLimit,
            ]);

            $response = $service->searchanalytics->query($propertyUrl, $request);
            $rows = $response->getRows() ?? [];

            // Convert Google objects to arrays for consistency
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'keys' => $row->getKeys(),
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => $row->getCtr(),
                    'position' => $row->getPosition(),
                ];
            }

            return new Collection($data);
        } catch (Exception $exception) {
            Log::error('Google Search Console - Failed to get search analytics', [
                'project_id' => $this->project->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function getKeywordData(Collection $keywords, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $keywordStrings = $keywords->pluck('keyword')->toArray();

        if (empty($keywordStrings)) {
            return new Collection();
        }

        $startDate ??= now()->subDays(7);
        $endDate ??= now()->subDays(1);
        $propertyUrl = $this->getCredential('property_url') ?? $this->project->url;

        try {
            $service = $this->getSearchConsoleService();

            $request = new SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate->format('Y-m-d'));
            $request->setEndDate($endDate->format('Y-m-d'));
            $request->setDimensions(['date', 'query', 'page']); // Add date dimension for daily data
            $request->setRowLimit(25000); // Increased limit to get more data
            $request->setStartRow(0);

            $response = $service->searchanalytics->query($propertyUrl, $request);
            $rows = $response->getRows() ?? [];

            // Convert Google objects to arrays
            $allRows = [];
            foreach ($rows as $row) {
                $allRows[] = [
                    'keys' => $row->getKeys(),
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => $row->getCtr(),
                    'position' => $row->getPosition(),
                ];
            }

            $allRows = new Collection($allRows);

            // Filter the results to only include our keywords
            $filteredRows = $allRows->filter(function (array $row) use ($keywordStrings): bool {
                $query = $row['keys'][1] ?? ''; // Query is now at index 1 because date is at index 0

                return in_array($query, $keywordStrings, true);
            });

            Log::debug('Google Search Console - Keyword data filtering', [
                'project_id' => $this->project->id,
                'total_rows' => $allRows->count(),
                'filtered_rows' => $filteredRows->count(),
                'our_keywords' => $keywordStrings,
                'all_found_keywords' => $allRows->map(fn (array $row): string => $row['keys'][1] ?? 'UNKNOWN')->unique()->values()->toArray(),
                'sample_raw_data' => $allRows->take(5)->toArray(),
                'sample_filtered_data' => $filteredRows->take(5)->map(fn (array $row): array => [
                    'date' => $row['keys'][0] ?? 'UNKNOWN',
                    'keyword' => $row['keys'][1] ?? 'UNKNOWN',
                    'page' => $row['keys'][2] ?? 'unknown',
                    'clicks' => $row['clicks'] ?? 0,
                    'impressions' => $row['impressions'] ?? 0,
                    'position' => round($row['position'] ?? 0, 2),
                ])->toArray(),
            ]);

            return $filteredRows;
        } catch (Exception $exception) {
            Log::error('Google Search Console - Failed to get keyword data', [
                'project_id' => $this->project->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function syncKeywordRankings(): int
    {
        // Only get high priority keywords, limit to 10
        /** @var \Illuminate\Database\Eloquent\Collection<int, Keyword> $keywords */
        $keywords = $this->project->keywords()
            ->where('priority', 'high')
            ->take(10)
            ->get();

        $synced = 0;

        // Process all keywords in one batch since we have max 10
        if ($keywords->isNotEmpty()) {
            try {
                // Get data for the last 7 days
                $startDate = now()->subDays(7);
                $endDate = now()->subDays(1);

                $searchAnalytics = $this->getKeywordData($keywords, $startDate, $endDate);

                Log::info('Google Search Console - Processing keywords individually', [
                    'project_id' => $this->project->id,
                    'total_analytics_rows' => $searchAnalytics->count(),
                    'keywords_to_process' => $keywords->count(),
                ]);

                // Process each keyword with daily data
                foreach ($keywords as $keyword) {
                    $keywordAnalytics = $searchAnalytics->filter(function (array $row) use ($keyword): bool {
                        return ($row['keys'][1] ?? '') === $keyword->keyword; // Query is at index 1 now
                    });

                    Log::debug('Google Search Console - Processing keyword', [
                        'project_id' => $this->project->id,
                        'keyword' => $keyword->keyword,
                        'found_rows' => $keywordAnalytics->count(),
                        'dates_found' => $keywordAnalytics->map(fn ($row): mixed => $row['keys'][0] ?? 'UNKNOWN')->unique()->values()->toArray(),
                    ]);

                    if ($keywordAnalytics->isNotEmpty()) {
                        // Process each day's data for this keyword
                        foreach ($keywordAnalytics as $dailyAnalytics) {
                            $this->createOrUpdateRanking($keyword, $dailyAnalytics);
                            $synced++;

                            Log::debug('Google Search Console - Synced daily record', [
                                'keyword' => $keyword->keyword,
                                'date' => $dailyAnalytics['keys'][0] ?? 'UNKNOWN',
                                'position' => $dailyAnalytics['position'] ?? null,
                                'total_synced_so_far' => $synced,
                            ]);
                        }
                    } else {
                        Log::warning('Google Search Console - No data found for keyword', [
                            'project_id' => $this->project->id,
                            'keyword' => $keyword->keyword,
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error('Google Search Console - Error syncing keywords', [
                    'project_id' => $this->project->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Google Search Console - Sync completed', [
            'project_id' => $this->project->id,
            'total_synced' => $synced,
            'keywords_processed' => $keywords->count(),
            'expected_max_records' => $keywords->count() * 7, // 7 days per keyword
        ]);

        return $synced;
    }

    private function createOrUpdateRanking(Keyword $keyword, array $analytics): void
    {
        $date = $analytics['keys'][0] ?? now()->format('Y-m-d'); // Date is at index 0
        $position = $analytics['position'] ?? null;
        $clicks = $analytics['clicks'] ?? 0;
        $impressions = $analytics['impressions'] ?? 0;
        $ctr = $analytics['ctr'] ?? 0;
        $page = $analytics['keys'][2] ?? 'unknown'; // Page URL is now at index 2

        // Get the latest ranking for this keyword to compare positions
        /** @var SearchConsoleRanking|null $latestRanking */
        $latestRanking = $keyword->project->searchConsoleRankings()
            ->where('query', $keyword->keyword)
            ->where('date_from', '<', $date)
            ->latest('date_from')
            ->first();

        $previousPosition = $latestRanking ? (float) $latestRanking->position : null;

        // Calculate position change
        $positionChange = null;
        if ($previousPosition !== null && $position !== null) {
            // Negative means improvement (went from position 10 to 5 = -5 = improved)
            // Positive means decline (went from position 5 to 10 = +5 = declined)
            $positionChange = (int) round($position - $previousPosition);
        }

        try {
            // Use updateOrCreate to avoid duplicates based on date and page URL
            $record = SearchConsoleRanking::query()->updateOrCreate(
                [
                    'project_id' => $keyword->project_id,
                    'keyword_id' => $keyword->id,
                    'query' => $keyword->keyword,
                    'page' => $page,
                    'date_from' => $date,
                    'date_to' => $date,
                ],
                [
                    'position' => $position ? round($position, 2) : null,
                    'previous_position' => $previousPosition,
                    'position_change' => $positionChange,
                    'clicks' => $clicks ?? 0,
                    'impressions' => $impressions ?? 0,
                    'ctr' => $ctr ?? 0,
                    'days_count' => 1, // Daily data is 1 day
                    'device' => 'desktop',
                    'country' => 'hun',
                    'fetched_at' => now(),
                ],
            );

            Log::debug('Google Search Console - Record saved', [
                'project_id' => $keyword->project_id,
                'keyword' => $keyword->keyword,
                'record_id' => $record->id,
                'date' => $date,
                'position' => $position,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'page' => $page,
            ]);
        } catch (Exception $e) {
            Log::error('Google Search Console - Failed to save record', [
                'project_id' => $keyword->project_id,
                'keyword' => $keyword->keyword,
                'date' => $date,
                'error' => $e->getMessage(),
                'data' => [
                    'project_id' => $keyword->project_id,
                    'keyword_id' => $keyword->id,
                    'query' => $keyword->keyword,
                    'page' => $page,
                    'date_from' => $date,
                    'date_to' => $date,
                    'position' => $position,
                    'clicks' => $clicks,
                    'impressions' => $impressions,
                    'ctr' => $ctr,
                ],
            ]);
            throw $e;
        }

        // Send notifications for significant changes
        if ($previousPosition && $position) {
            $this->checkForNotifications((float) $position, $previousPosition);
        }
    }

    private function checkForNotifications(float $currentPosition, float $previousPosition): void
    {
        $change = $previousPosition - $currentPosition;
        $changeType = null;

        // Determine notification type
        if ($currentPosition <= 3 && $previousPosition > 3) {
            $changeType = 'top3';
        } elseif ($currentPosition <= 10 && $previousPosition > 10) {
            $changeType = 'first_page';
        } elseif ($currentPosition > 10 && $previousPosition <= 10) {
            $changeType = 'dropped_out';
        } elseif (abs($change) >= 5) {
            $changeType = $change > 0 ? 'significant_improvement' : 'significant_decline';
        }

        // Here you would dispatch a notification job
        // We'll implement this in the notification system later
    }

    public function importKeywords(?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        Log::info('Google Search Console - Starting keyword import', [
            'project_id' => $this->project->id,
        ]);

        $startDate ??= now()->subDays(30);
        $endDate ??= now()->subDays(1);

        try {
            // Get search analytics data with query dimension
            $searchAnalytics = $this->getSearchAnalytics(['query'], $startDate, $endDate, 1000);

            $importedCount = 0;

            foreach ($searchAnalytics as $row) {
                $query = $row['keys'][0] ?? null;

                if (! $query || strlen((string) $query) < 2) {
                    continue; // Skip empty or too short queries
                }

                // Determine priority based on performance data
                $clicks = $row['clicks'] ?? 0;
                $impressions = $row['impressions'] ?? 0;
                $position = $row['position'] ?? 100;

                $priority = 'low'; // default
                if ($clicks > 0 && $position <= 20) {
                    $priority = 'high'; // Has clicks and good position
                } elseif ($impressions >= 100 && $position <= 30) {
                    $priority = 'medium'; // Good impressions and decent position
                } elseif ($clicks > 0 || ($impressions >= 50 && $position <= 50)) {
                    $priority = 'medium'; // Some engagement or potential
                }

                // Import/update keyword using updateOrCreate
                $keyword = Keyword::query()->updateOrCreate(
                    [
                        'project_id' => $this->project->id,
                        'keyword' => $query,
                    ],
                    [
                        'category' => 'imported',
                        'priority' => $priority,
                        'geo_target' => 'hun',
                        'intent_type' => 'informational',
                        'search_volume' => $impressions,
                        'difficulty' => null,
                        'last_updated' => now(),
                    ],
                );

                $importedCount++;

                Log::debug('Google Search Console - Imported keyword', [
                    'keyword' => $query,
                    'priority' => $priority,
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'position' => $position,
                ]);
            }

            Log::info('Google Search Console - Keyword import completed', [
                'project_id' => $this->project->id,
                'imported_count' => $importedCount,
                'date_range' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            ]);

            return $importedCount;
        } catch (Exception $e) {
            Log::error('Google Search Console - Error importing keywords', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
