<?php

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\Ranking;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

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
        $refreshToken = $this->getCredential('refresh_token');
        $clientId = $this->getCredential('client_id');
        $clientSecret = $this->getCredential('client_secret');

        if (! $refreshToken || ! $clientId || ! $clientSecret) {
            throw new Exception('Missing Google Search Console credentials');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (! $response->successful()) {
            throw new Exception('Failed to refresh Google Search Console access token');
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest()->get($this->baseUrl . '/sites');

            return $response->successful();
        } catch (Exception) {
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
        /** @var \Illuminate\Database\Eloquent\Collection<int, Keyword> $keywords */
        $keywords = $this->project->keywords()->get();
        $synced = 0;

        foreach ($keywords->chunk(50) as $keywordChunk) {
            $searchAnalytics = $this->getKeywordData($keywordChunk);

            foreach ($keywordChunk as $keyword) {
                $analytics = $searchAnalytics->firstWhere('keys.0', $keyword->keyword);

                if ($analytics) {
                    $this->createOrUpdateRanking($keyword, $analytics);
                    $synced++;
                }
            }

            // Rate limiting - pause between chunks
            usleep(500000); // 500ms
        }

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
