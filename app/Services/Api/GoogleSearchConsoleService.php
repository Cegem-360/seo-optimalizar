<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\SearchConsoleRanking;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
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
            if ($data !== []) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }

        curl_setopt_array($curl, $curlOptions);

        $response_body = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error !== '' && $error !== '0') {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            Log::error('Google Search Console - API request failed', [
                'project_id' => $this->project->id,
                'method' => $method,
                'url' => $url,
                'status' => $httpCode,
                'body' => $response_body,
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
            'service' => $this->serviceName,
        ]);

        $serviceAccountJson = $this->getCredential('service_account_json');

        Log::debug('Google Search Console - Service account check', [
            'project_id' => $this->project->id,
            'has_service_account' => ! empty($serviceAccountJson),
            'service_account_email' => isset($serviceAccountJson['client_email']) ? substr((string) $serviceAccountJson['client_email'], 0, 20) . '...' : 'MISSING',
        ]);

        if (! $serviceAccountJson || empty($serviceAccountJson['private_key']) || empty($serviceAccountJson['client_email'])) {
            throw new Exception('Missing Google Search Console service account credentials');
        }

        // Create JWT token for Service Account authentication
        $now = time();
        $expiry = $now + 3600; // 1 hour

        $header = rtrim(strtr(base64_encode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ])), '+/', '-_'), '=');

        $payload = rtrim(strtr(base64_encode(json_encode([
            'iss' => $serviceAccountJson['client_email'],
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $expiry,
            'iat' => $now,
        ])), '+/', '-_'), '=');

        $signature_input = $header . '.' . $payload;

        // Sign with private key
        $private_key = $serviceAccountJson['private_key'];
        openssl_sign($signature_input, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $signature = rtrim(strtr(base64_encode((string) $signature), '+/', '-_'), '=');

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

        if ($error !== '' && $error !== '0') {
            Log::error('Google Search Console - cURL error during token refresh', [
                'project_id' => $this->project->id,
                'error' => $error,
            ]);
            throw new Exception('cURL error: ' . $error);
        }

        Log::debug('Google Search Console - Token refresh response', [
            'project_id' => $this->project->id,
            'status' => $httpCode,
            'successful' => $httpCode === 200,
        ]);

        if ($httpCode !== 200) {
            Log::error('Google Search Console - Failed to refresh access token', [
                'project_id' => $this->project->id,
                'status' => $httpCode,
                'body' => $response_body,
            ]);
            throw new Exception('Failed to refresh Google Search Console access token. HTTP ' . $httpCode . ': ' . $response_body);
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Google Search Console - Invalid JSON response', [
                'project_id' => $this->project->id,
                'json_error' => json_last_error_msg(),
                'response_body' => $response_body,
            ]);
            throw new Exception('Invalid JSON response: ' . json_last_error_msg());
        }

        $this->accessToken = $data['access_token'];

        Log::info('Google Search Console - Service account access token obtained successfully', [
            'project_id' => $this->project->id,
            'token_length' => strlen((string) $this->accessToken),
        ]);
    }

    public function testConnection(): bool
    {
        Log::info('Google Search Console - Testing connection', [
            'project_id' => $this->project->id,
            'service' => $this->serviceName,
        ]);

        try {
            $data = $this->makeApiRequest('GET', $this->baseUrl . '/sites');

            Log::info('Google Search Console - Connection test successful', [
                'project_id' => $this->project->id,
                'sites_count' => count($data['siteEntry'] ?? []),
            ]);

            return true;
        } catch (Exception $exception) {
            Log::error('Google Search Console - Connection test error', [
                'project_id' => $this->project->id,
                'error' => $exception->getMessage(),
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
        $propertyUrl = $this->getCredential('property_url') ?? $siteUrl;

        $payload = [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'dimensions' => $dimensions,
            'rowLimit' => $rowLimit,
            'startRow' => 0,
        ];

        Log::info('Google Search Console - Fetching search analytics', [
            'project_id' => $this->project->id,
            'site_url' => $propertyUrl,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'dimensions' => $dimensions,
            'row_limit' => $rowLimit,
        ]);

        $data = $this->makeApiRequest('POST', $this->baseUrl . '/sites/' . urlencode($propertyUrl) . '/searchAnalytics/query', $payload);

        Log::debug('Google Search Console - Search analytics response', [
            'project_id' => $this->project->id,
            'total_rows' => count($data['rows'] ?? []),
            'sample_data' => array_slice($data['rows'] ?? [], 0, 5), // First 5 rows for debugging
        ]);

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
        $propertyUrl = $this->getCredential('property_url') ?? $this->project->url;

        // Get aggregated data without date dimension for the period
        $payload = [
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'dimensions' => ['query', 'page'], // Add page dimension to get URLs
            'rowLimit' => 25000, // Increased limit to get more data
            'aggregationType' => 'byPage', // Aggregate by page
        ];

        Log::info('Google Search Console - Fetching keyword data', [
            'project_id' => $this->project->id,
            'property_url' => $propertyUrl,
            'keyword_count' => count($keywordStrings),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'sample_keywords' => array_slice($keywordStrings, 0, 5),
        ]);

        $data = $this->makeApiRequest('POST', $this->baseUrl . '/sites/' . urlencode($propertyUrl) . '/searchAnalytics/query', $payload);

        $allRows = new Collection($data['rows'] ?? []);

        // Filter the results to only include our keywords
        $filteredRows = $allRows->filter(function ($row) use ($keywordStrings) {
            $query = $row['keys'][0] ?? '';

            return in_array($query, $keywordStrings, true);
        });

        Log::debug('Google Search Console - Keyword data response', [
            'project_id' => $this->project->id,
            'total_rows' => $allRows->count(),
            'filtered_rows' => $filteredRows->count(),
            'detailed_results' => $filteredRows->take(10)->map(fn (array $row): array => [
                'keyword' => $row['keys'][0] ?? 'UNKNOWN',
                'page' => $row['keys'][1] ?? 'unknown',
                'clicks' => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr' => $row['ctr'] ?? 0,
                'position' => round($row['position'] ?? 0, 2),
            ])->toArray(),
        ]);

        return $filteredRows;
    }

    public function syncKeywordRankings(): int
    {
        Log::info('Google Search Console - Starting keyword ranking sync', [
            'project_id' => $this->project->id,
        ]);

        // Only get high priority keywords, limit to 10
        /** @var \Illuminate\Database\Eloquent\Collection<int, Keyword> $keywords */
        $keywords = $this->project->keywords()
            ->where('priority', 'high')
            ->take(10)
            ->get();

        $synced = 0;

        Log::info('Google Search Console - Found priority keywords to sync', [
            'project_id' => $this->project->id,
            'keyword_count' => $keywords->count(),
        ]);

        // Process all keywords in one batch since we have max 10
        if ($keywords->isNotEmpty()) {
            try {
                // Get data for the last 7 days
                $startDate = now()->subDays(7);
                $endDate = now()->subDays(1);

                $searchAnalytics = $this->getKeywordData($keywords, $startDate, $endDate);

                Log::info('Google Search Console - Processing search analytics', [
                    'project_id' => $this->project->id,
                    'analytics_count' => $searchAnalytics->count(),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]);

                foreach ($keywords as $keyword) {
                    $analytics = $searchAnalytics->firstWhere('keys.0', $keyword->keyword);

                    if ($analytics) {
                        $this->createOrUpdateRanking($keyword, $analytics);
                        $synced++;

                        Log::debug('Google Search Console - Synced keyword', [
                            'keyword' => $keyword->keyword,
                            'position' => $analytics['position'] ?? null,
                            'clicks' => $analytics['clicks'] ?? 0,
                            'impressions' => $analytics['impressions'] ?? 0,
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

        return $synced;
    }

    private function createOrUpdateRanking(Keyword $keyword, array $analytics): void
    {
        $position = $analytics['position'] ?? null;
        $clicks = $analytics['clicks'] ?? 0;
        $impressions = $analytics['impressions'] ?? 0;
        $ctr = $analytics['ctr'] ?? 0;
        $page = $analytics['keys'][1] ?? 'unknown'; // Get the page URL from keys array

        // Get the latest ranking to compare positions
        /** @var SearchConsoleRanking|null $latestRanking */
        $latestRanking = $keyword->project->searchConsoleRankings()->where('query', $keyword->keyword)->latest('fetched_at')->first();
        $previousPosition = $latestRanking ? (float) $latestRanking->position : null;

        // Calculate position change
        $positionChange = null;
        if ($previousPosition !== null && $position !== null) {
            // Negative means improvement (went from position 10 to 5 = -5 = improved)
            // Positive means decline (went from position 5 to 10 = +5 = declined)
            $positionChange = (int) round($position - $previousPosition);
        }

        SearchConsoleRanking::query()->create([
            'project_id' => $keyword->project_id,
            'keyword_id' => $keyword->id,
            'query' => $keyword->keyword,
            'page' => $page,
            'position' => $position ? round($position, 2) : null,
            'previous_position' => $previousPosition,
            'position_change' => $positionChange,
            'clicks' => $clicks ?? 0,
            'impressions' => $impressions ?? 0,
            'ctr' => $ctr ?? 0,
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->subDays(1)->format('Y-m-d'),
            'days_count' => 7,
            'device' => 'desktop',
            'country' => 'hun',
            'fetched_at' => now(),
        ]);

        // Send notifications for significant changes
        if ($previousPosition && $position) {
            $this->checkForNotifications((float) $position, (float) $previousPosition);
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
}
