<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Keyword;
use Exception;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Lib\V21\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V21\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\V21\Enums\KeywordPlanNetworkEnum\KeywordPlanNetwork;
use Google\Ads\GoogleAds\V21\Services\GenerateKeywordHistoricalMetricsRequest;
use Google\Ads\GoogleAds\V21\Services\GenerateKeywordIdeasRequest;
use Google\Ads\GoogleAds\V21\Services\KeywordSeed;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GoogleAdsApiService extends BaseApiService
{
    protected string $serviceName = 'google_ads';

    private ?GoogleAdsClient $googleAdsClient = null;

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        // Google Ads uses its own client, not HTTP requests
    }

    public function testConnection(): bool
    {
        try {
            $client = $this->getClient();

            return $client instanceof GoogleAdsClient;
        } catch (Exception) {
            return false;
        }
    }

    private function getClient(): ?GoogleAdsClient
    {
        if ($this->googleAdsClient instanceof GoogleAdsClient) {
            return $this->googleAdsClient;
        }

        try {
            $clientId = $this->getCredential('client_id');
            $clientSecret = $this->getCredential('client_secret');
            $refreshToken = $this->getCredential('refresh_token');
            $developerToken = $this->getCredential('developer_token');
            $customerId = $this->getCredential('customer_id');

            if (! $clientId || ! $clientSecret || ! $refreshToken || ! $developerToken || ! $customerId) {
                throw new Exception('Google Ads credentials are incomplete. Missing: ' .
                    ($clientId ? '' : 'client_id ') .
                    ($clientSecret ? '' : 'client_secret ') .
                    ($refreshToken ? '' : 'refresh_token ') .
                    ($developerToken ? '' : 'developer_token ') .
                    ($customerId ? '' : 'customer_id')
                );
            }

            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId($clientId)
                ->withClientSecret($clientSecret)
                ->withRefreshToken($refreshToken)
                ->build();

            $this->googleAdsClient = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken($developerToken)
                ->build();

            return $this->googleAdsClient;
        } catch (Exception $exception) {
            Log::error('Google Ads API client error', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function getKeywordData(string $keyword, string $countryCode = 'HU'): ?array
    {
        try {
            $client = $this->getClient();
            if (! $client instanceof GoogleAdsClient) {
                return null;
            }

            $keywordPlanIdeaService = $client->getKeywordPlanIdeaServiceClient();
            $customerId = $this->getCredential('customer_id');

            // Create request
            $generateKeywordIdeasRequest = new GenerateKeywordIdeasRequest();
            $generateKeywordIdeasRequest->setCustomerId($customerId);
            $generateKeywordIdeasRequest->setKeywordSeed(
                (new KeywordSeed())
                    ->setKeywords([$keyword])
            );
            $generateKeywordIdeasRequest->setKeywordPlanNetwork(KeywordPlanNetwork::GOOGLE_SEARCH);

            // Set geo targeting
            $geoTargetConstant = $this->getGeoTargetConstant($countryCode);
            if ($geoTargetConstant !== '' && $geoTargetConstant !== '0') {
                $generateKeywordIdeasRequest->setGeoTargetConstants([$geoTargetConstant]);
            }

            $response = $keywordPlanIdeaService->generateKeywordIdeas($generateKeywordIdeasRequest);

            foreach ($response as $idea) {
                if (strtolower((string) $idea->getText()) === strtolower($keyword)) {
                    $searchVolume = $idea->getKeywordIdeaMetrics()?->getAvgMonthlySearches();
                    $competition = $idea->getKeywordIdeaMetrics()?->getCompetition();
                    $lowTopPageBid = $idea->getKeywordIdeaMetrics()?->getLowTopOfPageBidMicros();
                    $highTopPageBid = $idea->getKeywordIdeaMetrics()?->getHighTopOfPageBidMicros();

                    return [
                        'keyword' => $keyword,
                        'search_volume' => $searchVolume ?? 0,
                        'competition' => $this->mapCompetition($competition),
                        'low_bid' => $lowTopPageBid ? $lowTopPageBid / 1000000 : 0,
                        'high_bid' => $highTopPageBid ? $highTopPageBid / 1000000 : 0,
                        'difficulty' => $this->calculateDifficulty($competition, $searchVolume ?? 0),
                    ];
                }
            }

            return null;
        } catch (Exception $exception) {
            Log::error('Google Ads API error', [
                'keyword' => $keyword,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function bulkGetKeywordData(Collection $keywords, string $countryCode = 'HU'): array
    {
        $results = [];

        foreach ($keywords as $keyword) {
            $keywordText = $keyword instanceof Keyword ? $keyword->keyword : $keyword;
            $data = $this->getKeywordData($keywordText, $countryCode);

            if ($data !== null && $data !== []) {
                $results[$keywordText] = $data;
            }

            // Rate limiting - be respectful to the API
            usleep(100000); // 0.1 seconds
        }

        return $results;
    }

    public function updateKeywordMetrics(Keyword $keyword, string $countryCode = 'HU'): bool
    {
        $geoTarget = $keyword->geo_target ?? 'HU';
        $data = $this->getKeywordData($keyword->keyword, $this->getCountryCodeFromGeoTarget($geoTarget));

        if ($data === null || $data === []) {
            return false;
        }

        $keyword->update([
            'search_volume' => $data['search_volume'],
            'difficulty_score' => $data['difficulty'],
        ]);

        return true;
    }

    public function updateProjectKeywords(int $batchSize = 20): int
    {
        $keywords = $this->project->keywords()
            ->whereNull('search_volume')
            ->orWhereNull('difficulty_score')
            ->limit($batchSize)
            ->get();

        $updated = 0;

        /** @var Keyword $keyword */
        foreach ($keywords as $keyword) {
            try {
                if ($this->updateKeywordMetrics($keyword)) {
                    $updated++;
                }

                // Rate limiting
                usleep(200000); // 0.2 seconds
            } catch (Exception $e) {
                Log::warning('Failed to update keyword metrics', [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $updated;
    }

    private function mapCompetition(?int $competition): float
    {
        return match ($competition) {
            1 => 0.1, // LOW
            2 => 0.5, // MEDIUM
            3 => 0.9, // HIGH
            default => 0.0,
        };
    }

    private function calculateDifficulty(?int $competition, int $searchVolume): int
    {
        $difficultyScore = 0;

        // Competition contributes 70% to difficulty
        $competitionScore = match ($competition) {
            1 => 20,  // LOW
            2 => 50,  // MEDIUM
            3 => 80,  // HIGH
            default => 10,
        };
        $difficultyScore += $competitionScore * 0.7;

        // Search volume contributes 30% (higher volume = higher difficulty)
        if ($searchVolume > 0) {
            $volumeScore = min($searchVolume / 1000, 100); // Normalize
            $difficultyScore += $volumeScore * 0.3;
        }

        return min(100, max(1, (int) round($difficultyScore)));
    }

    private function getGeoTargetConstant(string $countryCode): string
    {
        $geoTargets = [
            'HU' => 'geoTargetConstants/2348', // Hungary
            'US' => 'geoTargetConstants/2840', // United States
            'UK' => 'geoTargetConstants/2826', // United Kingdom
            'DE' => 'geoTargetConstants/2276', // Germany
            'FR' => 'geoTargetConstants/2250', // France
        ];

        return $geoTargets[strtoupper($countryCode)] ?? $geoTargets['HU'];
    }

    private function getCountryCodeFromGeoTarget(string $geoTarget): string
    {
        return match (strtolower($geoTarget)) {
            'hu', 'hungary', 'magyarország' => 'HU',
            'us', 'usa', 'united states' => 'US',
            'uk', 'gb', 'united kingdom' => 'UK',
            'de', 'germany' => 'DE',
            'fr', 'france' => 'FR',
            'global' => 'US',
            default => 'HU',
        };
    }

    public function getHistoricalMetrics(string $keyword, string $countryCode = 'HU'): ?array
    {
        try {
            $client = $this->getClient();
            if (! $client instanceof GoogleAdsClient) {
                return null;
            }

            $keywordPlanIdeaService = $client->getKeywordPlanIdeaServiceClient();
            $customerId = $this->getCredential('customer_id');

            // Create request
            $generateKeywordHistoricalMetricsRequest = new GenerateKeywordHistoricalMetricsRequest();
            $generateKeywordHistoricalMetricsRequest->setCustomerId($customerId);
            $generateKeywordHistoricalMetricsRequest->setKeywords([$keyword]);
            $generateKeywordHistoricalMetricsRequest->setKeywordPlanNetwork(KeywordPlanNetwork::GOOGLE_SEARCH);

            // Set geo targeting
            $geoTargetConstant = $this->getGeoTargetConstant($countryCode);
            if ($geoTargetConstant !== '' && $geoTargetConstant !== '0') {
                $generateKeywordHistoricalMetricsRequest->setGeoTargetConstants([$geoTargetConstant]);
            }

            $response = $keywordPlanIdeaService->generateKeywordHistoricalMetrics($generateKeywordHistoricalMetricsRequest);

            foreach ($response->getResults() as $result) {
                if (strtolower((string) $result->getText()) === strtolower($keyword)) {
                    $metrics = $result->getKeywordMetrics();
                    if ($metrics === null) {
                        continue;
                    }

                    $monthlySearchVolumes = [];
                    foreach ($metrics->getMonthlySearchVolumes() as $monthlySearchVolume) {
                        $monthlySearchVolumes[] = [
                            'year' => $monthlySearchVolume->getYear(),
                            'month' => $monthlySearchVolume->getMonth(),
                            'monthly_searches' => $monthlySearchVolume->getMonthlySearches(),
                        ];
                    }

                    return [
                        'keyword' => $keyword,
                        'avg_monthly_searches' => $metrics->getAvgMonthlySearches(),
                        'competition' => $this->mapCompetition($metrics->getCompetition()),
                        'competition_index' => $metrics->getCompetitionIndex(),
                        'low_top_of_page_bid_micros' => $metrics->getLowTopOfPageBidMicros(),
                        'high_top_of_page_bid_micros' => $metrics->getHighTopOfPageBidMicros(),
                        'low_top_of_page_bid' => $metrics->getLowTopOfPageBidMicros() ? $metrics->getLowTopOfPageBidMicros() / 1000000 : 0,
                        'high_top_of_page_bid' => $metrics->getHighTopOfPageBidMicros() ? $metrics->getHighTopOfPageBidMicros() / 1000000 : 0,
                        'monthly_search_volumes' => $monthlySearchVolumes,
                    ];
                }
            }

            return null;
        } catch (Exception $exception) {
            Log::error('Google Ads Historical Metrics API error', [
                'keyword' => $keyword,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function updateKeywordHistoricalMetrics(Keyword $keyword, string $countryCode = 'HU'): bool
    {
        $geoTarget = $keyword->geo_target ?? 'HU';
        $data = $this->getHistoricalMetrics($keyword->keyword, $this->getCountryCodeFromGeoTarget($geoTarget));

        if ($data === null || $data === []) {
            return false;
        }

        $keyword->update([
            'search_volume' => $data['avg_monthly_searches'],
            'competition_index' => $data['competition_index'],
            'low_top_of_page_bid' => $data['low_top_of_page_bid'],
            'high_top_of_page_bid' => $data['high_top_of_page_bid'],
            'monthly_search_volumes' => $data['monthly_search_volumes'],
            'historical_metrics_updated_at' => now(),
            'difficulty_score' => $this->calculateDifficultyFromIndex($data['competition_index'], $data['avg_monthly_searches']),
        ]);

        return true;
    }

    public function updateProjectKeywordsWithHistoricalMetrics(int $batchSize = 10): int
    {
        $keywords = $this->project->keywords()
            ->where(function ($query): void {
                $query->whereNull('historical_metrics_updated_at')
                    ->orWhere('historical_metrics_updated_at', '<', now()->subDays(7));
            })
            ->limit($batchSize)
            ->get();

        $updated = 0;

        /** @var Keyword $keyword */
        foreach ($keywords as $keyword) {
            try {
                if ($this->updateKeywordHistoricalMetrics($keyword)) {
                    $updated++;
                }

                // Rate limiting - be more conservative with historical metrics
                usleep(500000); // 0.5 seconds
            } catch (Exception $e) {
                Log::warning('Failed to update keyword historical metrics', [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $updated;
    }

    private function calculateDifficultyFromIndex(?int $competitionIndex, int $searchVolume): int
    {
        if ($competitionIndex === null) {
            return $this->calculateDifficulty(null, $searchVolume);
        }

        // Competition index is 0-100, we can use it more directly
        $difficultyScore = 0;

        // Competition index contributes 70% to difficulty
        $difficultyScore += $competitionIndex * 0.7;

        // Search volume contributes 30% (higher volume = higher difficulty)
        if ($searchVolume > 0) {
            $volumeScore = min($searchVolume / 1000, 100); // Normalize
            $difficultyScore += $volumeScore * 0.3;
        }

        return min(100, max(1, (int) round($difficultyScore)));
    }
}
