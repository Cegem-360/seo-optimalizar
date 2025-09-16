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
        // This method is overridden - we use direct cURL calls instead
    }

    private function makeApiRequest(string $method, string $url, array $data = []): array
    {
        if ($this->accessToken === null || $this->accessToken === '' || $this->accessToken === '0') {
            $this->refreshAccessToken();
        }

        $curl = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        if ($method === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            if (!empty($data)) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($curl, $curlOptions);

        $response_body = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Log::error('Google Search Console - API request failed', [
                'project_id' => $this->project->id,
                'method' => $method,
                'url' => $url,
                'status' => $httpCode,
                'body' => $response_body
            ]);
            throw new Exception('API request failed. HTTP ' . $httpCode . ': ' . $response_body);
        }

        $data = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
    }

    private function refreshAccessToken(): void
    {
        Log::info('Google Search Console - Getting service account access token', [
            'project_id' => $this->project->id,
            'service' => $this->serviceName
        ]);

        $serviceAccountJson = $this->getCredential('service_account_json');

        Log::debug('Google Search Console - Service account check', [
            'project_id' => $this->project->id,
            'has_service_account' => !empty($serviceAccountJson),
            'service_account_email' => $serviceAccountJson['client_email'] ?? 'MISSING'
        ]);

        if (!$serviceAccountJson || empty($serviceAccountJson['private_key']) || empty($serviceAccountJson['client_email'])) {
            throw new Exception('Missing Google Search Console service account credentials');
        }

        // Create JWT token for Service Account authentication
        $now = time();
        $expiry = $now + 3600; // 1 hour

        $header = rtrim(strtr(base64_encode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT'
        ])), '+/', '-_'), '=');

        $payload = rtrim(strtr(base64_encode(json_encode([
            'iss' => $serviceAccountJson['client_email'],
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $expiry,
            'iat' => $now
        ])), '+/', '-_'), '=');

        $signature_input = $header . '.' . $payload;

        // Sign with private key
        $private_key = $serviceAccountJson['private_key'];
        openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $jwt = $signature_input . '.' . $signature;

        // Exchange JWT for access token
        $postData = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen($postData),
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response_body = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            Log::error('Google Search Console - cURL error during token refresh', [
                'project_id' => $this->project->id,
                'error' => $error
            ]);
            throw new Exception('cURL error: ' . $error);
        }

        Log::debug('Google Search Console - Token refresh response', [
            'project_id' => $this->project->id,
            'status' => $httpCode,
            'successful' => $httpCode === 200
        ]);

        if ($httpCode !== 200) {
            Log::error('Google Search Console - Failed to refresh access token', [
                'project_id' => $this->project->id,
                'status' => $httpCode,
                'body' => $response_body
            ]);
            throw new Exception('Failed to refresh Google Search Console access token. HTTP ' . $httpCode . ': ' . $response_body);
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Google Search Console - Invalid JSON response', [
                'project_id' => $this->project->id,
                'json_error' => json_last_error_msg(),
                'response_body' => $response_body
            ]);
            throw new Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        $this->accessToken = $data['access_token'];

        Log::info('Google Search Console - Service account access token obtained successfully', [
            'project_id' => $this->project->id,
            'token_length' => strlen($this->accessToken),
            'service_account_email' => $serviceAccountJson['client_email']
        ]);
    }

    public function testConnection(): bool
    {
        Log::info('Google Search Console - Testing connection', [
            'project_id' => $this->project->id,
            'service' => $this->serviceName
        ]);

        try {
            $data = $this->makeApiRequest('GET', $this->baseUrl . '/sites');

            Log::info('Google Search Console - Connection test successful', [
                'project_id' => $this->project->id,
                'sites_count' => count($data['siteEntry'] ?? [])
            ]);
            return true;

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
        $data = $this->makeApiRequest('GET', $this->baseUrl . '/sites');
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

        $data = $this->makeApiRequest('POST', $this->baseUrl . '/sites/' . urlencode($siteUrl) . '/searchAnalytics/query', $payload);

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

        $data = $this->makeApiRequest('POST', $this->baseUrl . '/sites/' . urlencode($siteUrl) . '/searchAnalytics/query', $payload);

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
