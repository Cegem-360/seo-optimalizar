<?php

namespace App\Services\Api;

use App\Models\Keyword;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V17\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\V17\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V17\Services\KeywordPlanIdeaServiceClient;
use Google\Ads\GoogleAds\V17\Services\GenerateKeywordIdeasRequest;
use Google\Ads\GoogleAds\V17\Common\KeywordInfo;
use Google\Ads\GoogleAds\V17\Enums\KeywordPlanNetworkEnum\KeywordPlanNetwork;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

class GoogleAdsApiService extends BaseApiService
{
    protected string $serviceName = 'google_ads';
    private ?GoogleAdsClient $client = null;

    protected function configureRequest(PendingRequest $request): void
    {
        // Google Ads uses its own client, not HTTP requests
    }

    public function testConnection(): bool
    {
        try {
            $client = $this->getClient();
            return $client !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getClient(): ?GoogleAdsClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        try {
            $oAuth2Credential = (new OAuth2TokenBuilder())
                ->withClientId($this->getCredential('client_id'))
                ->withClientSecret($this->getCredential('client_secret'))
                ->withRefreshToken($this->getCredential('refresh_token'))
                ->build();

            $this->client = (new GoogleAdsClientBuilder())
                ->withOAuth2Credential($oAuth2Credential)
                ->withDeveloperToken($this->getCredential('developer_token'))
                ->build();

            return $this->client;
        } catch (\Exception $e) {
            \Log::error('Google Ads API client error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getKeywordData(string $keyword, string $countryCode = 'HU'): ?array
    {
        try {
            $client = $this->getClient();
            if (!$client) {
                return null;
            }

            $keywordPlanIdeaService = $client->getKeywordPlanIdeaServiceClient();
            $customerId = $this->getCredential('customer_id');

            // Create keyword info
            $keywordInfo = new KeywordInfo();
            $keywordInfo->setText($keyword);

            // Create request
            $request = new GenerateKeywordIdeasRequest();
            $request->setCustomerId($customerId);
            $request->setKeywordSeed(
                (new \Google\Ads\GoogleAds\V17\Services\KeywordSeed())
                    ->setKeywords([$keywordInfo])
            );
            $request->setKeywordPlanNetwork(KeywordPlanNetwork::GOOGLE_SEARCH);
            
            // Set geo targeting
            $geoTargetConstant = $this->getGeoTargetConstant($countryCode);
            if ($geoTargetConstant) {
                $request->setGeoTargetConstants([$geoTargetConstant]);
            }

            $response = $keywordPlanIdeaService->generateKeywordIdeas($request);

            foreach ($response as $idea) {
                if (strtolower($idea->getText()) === strtolower($keyword)) {
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
        } catch (\Exception $e) {
            \Log::error('Google Ads API error', [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
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
            
            if ($data) {
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
        
        if (!$data) {
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
        
        foreach ($keywords as $keyword) {
            try {
                if ($this->updateKeywordMetrics($keyword)) {
                    $updated++;
                }
                
                // Rate limiting
                usleep(200000); // 0.2 seconds
            } catch (\Exception $e) {
                \Log::warning('Failed to update keyword metrics', [
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
        return match($competition) {
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
        $competitionScore = match($competition) {
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

    private function getGeoTargetConstant(string $countryCode): ?string
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
        return match(strtolower($geoTarget)) {
            'hu', 'hungary', 'magyarország' => 'HU',
            'us', 'usa', 'united states' => 'US',
            'uk', 'gb', 'united kingdom' => 'UK',
            'de', 'germany' => 'DE',
            'fr', 'france' => 'FR',
            'global' => 'US',
            default => 'HU',
        };
    }
}