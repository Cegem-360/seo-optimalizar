<?php

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\Ranking;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSearchConsoleService extends BaseApiService
{
    protected string $serviceName = 'google_search_console';

    private string $baseUrl = 'https://www.googleapis.com/webmasters/v3';

    private ?string $accessToken = null;

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        if ($this->accessToken === null || $this->accessToken === '' || $this->accessToken === '0') {
            $this->refreshAccessToken();
        }

        $pendingRequest->withToken($this->accessToken)
            ->accept('application/json');
    }

    private function refreshAccessToken(): void
    {
        Log::info('Google Search Console - Refreshing access token', [
            'project_id' => $this->project->id,
            'service' => $this->serviceName
        ]);

        $refreshToken = $this->getCredential('refresh_token');
        $clientId = $this->getCredential('client_id');
        $clientSecret = $this->getCredential('client_secret');

        Log::debug('Google Search Console - Credentials check', [
            'project_id' => $this->project->id,
            'has_refresh_token' => !empty($refreshToken),
            'has_client_id' => !empty($clientId),
            'has_client_secret' => !empty($clientSecret)
        ]);

        if (! $refreshToken || ! $clientId || ! $clientSecret) {
            throw new Exception('Missing Google Search Console credentials');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        Log::debug('Google Search Console - Token refresh response', [
            'project_id' => $this->project->id,
            'status' => $response->status(),
            'successful' => $response->successful()
        ]);

        if (! $response->successful()) {
            Log::error('Google Search Console - Failed to refresh access token', [
                'project_id' => $this->project->id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new Exception('Failed to refresh Google Search Console access token');
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];

        Log::info('Google Search Console - Access token refreshed successfully', [
            'project_id' => $this->project->id,
            'token_length' => strlen($this->accessToken)
        ]);
    }

    public function testConnection(): bool
    {
        Log::info('Google Search Console - Testing connection', [
            'project_id' => $this->project->id,
            'service' => $this->serviceName
        ]);

        try {
            $response = $this->makeRequest()->get($this->baseUrl . '/sites');

            Log::debug('Google Search Console - Connection test response', [
                'project_id' => $this->project->id,
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                Log::info('Google Search Console - Connection test successful', [
                    'project_id' => $this->project->id
                ]);
                return true;
            } else {
                Log::warning('Google Search Console - Connection test failed', [
                    'project_id' => $this->project->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (Exception $e) {
            Log::error('Google Search Console - Connection test error', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getSites(): Collection
    {
        $response = $this->makeRequest()->get($this->baseUrl . '/sites');
        $data = $this->handleResponse($response);

        return new Collection($data['siteEntry'] ?? []);
    }

    public function getSearchAnalytics(array $dimensions = ['query'], ?Carbon $startDate = null, ?Carbon $endDate = null, int $rowLimit = 1000): Collection
    {
        $startDate ??= now()->subDays(7);
        $endDate ??= now()->subDays(1);

        $siteUrl = $this->project->url;

        $payload = [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'dimensions' => $dimensions,
            'rowLimit' => $rowLimit,
            'startRow' => 0,
        ];

        $response = $this->makeRequest()
            ->post($this->baseUrl . '/sites/' . urlencode($siteUrl) . '/searchAnalytics/query', $payload);

        $data = $this->handleResponse($response);

        return new Collection($data['rows'] ?? []);
    }

    public function getKeywordData(Collection $keywords, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $keywordStrings = $keywords->pluck('keyword')->toArray();

        if (empty($keywordStrings)) {
            return new Collection();
        }

        $startDate ??= now()->subDays(7);
        $endDate ??= now()->subDays(1);
        $siteUrl = $this->project->url;

        $payload = [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'dimensions' => ['query'],
            'dimensionFilterGroups' => [
                [
                    'filters' => [
                        [
                            'dimension' => 'query',
                            'operator' => 'contains',
                            'expression' => implode('|', $keywordStrings),
                        ],
                    ],
                ],
            ],
            'rowLimit' => 1000,
        ];

        $response = $this->makeRequest()
            ->post($this->baseUrl . '/sites/' . urlencode($siteUrl) . '/searchAnalytics/query', $payload);

        $data = $this->handleResponse($response);

        return new Collection($data['rows'] ?? []);
    }

    public function syncKeywordRankings(): int
    {
        Log::info('Google Search Console - Starting keyword ranking sync', [
            'project_id' => $this->project->id
        ]);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Keyword> $keywords */
        $keywords = $this->project->keywords()->get();
        $synced = 0;

        Log::info('Google Search Console - Found keywords to sync', [
            'project_id' => $this->project->id,
            'keyword_count' => $keywords->count()
        ]);

        foreach ($keywords->chunk(50) as $chunkIndex => $keywordChunk) {
            Log::debug('Google Search Console - Processing keyword chunk', [
                'project_id' => $this->project->id,
                'chunk' => $chunkIndex + 1,
                'chunk_size' => $keywordChunk->count()
            ]);

            try {
                $searchAnalytics = $this->getKeywordData($keywordChunk);

                Log::debug('Google Search Console - Retrieved analytics data', [
                    'project_id' => $this->project->id,
                    'chunk' => $chunkIndex + 1,
                    'analytics_count' => $searchAnalytics->count()
                ]);

                foreach ($keywordChunk as $keyword) {
                    $analytics = $searchAnalytics->firstWhere('keys.0', $keyword->keyword);

                    if ($analytics) {
                        $this->createOrUpdateRanking($keyword, $analytics);
                        $synced++;

                        Log::debug('Google Search Console - Updated keyword ranking', [
                            'project_id' => $this->project->id,
                            'keyword' => $keyword->keyword,
                            'position' => $analytics['position'] ?? null,
                            'clicks' => $analytics['clicks'] ?? 0,
                            'impressions' => $analytics['impressions'] ?? 0
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error('Google Search Console - Error processing keyword chunk', [
                    'project_id' => $this->project->id,
                    'chunk' => $chunkIndex + 1,
                    'error' => $e->getMessage()
                ]);
            }

            // Rate limiting - pause between chunks
            usleep(500000); // 500ms
        }

        Log::info('Google Search Console - Keyword ranking sync completed', [
            'project_id' => $this->project->id,
            'synced_count' => $synced,
            'total_keywords' => $keywords->count()
        ]);

        return $synced;
    }

    private function createOrUpdateRanking(Keyword $keyword, array $analytics): void
    {
        $position = $analytics['position'] ?? null;
        $clicks = $analytics['clicks'] ?? 0;
        $impressions = $analytics['impressions'] ?? 0;
        $ctr = $analytics['ctr'] ?? 0;

        // Get the latest ranking to compare positions
        /** @var Ranking|null $latestRanking */
        $latestRanking = $keyword->rankings()->latest('checked_at')->first();
        $previousPosition = $latestRanking?->position;

        Ranking::query()->create([
            'keyword_id' => $keyword->id,
            'position' => $position ? round($position) : null,
            'previous_position' => $previousPosition,
            'url' => null, // GSC doesn't provide specific URL in search analytics
            'featured_snippet' => false, // Would need separate API call to detect
            'serp_features' => [
                'clicks' => $clicks,
                'impressions' => $impressions,
                'ctr' => $ctr,
            ],
            'checked_at' => now(),
        ]);

        // Send notifications for significant changes
        if ($previousPosition && $position) {
            $this->checkForNotifications($position, $previousPosition);
        }
    }

    private function checkForNotifications(int $currentPosition, int $previousPosition): void
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
}
