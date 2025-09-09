<?php

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\Ranking;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;

class SerpApiService extends BaseApiService
{
    protected string $serviceName = 'serpapi';

    private string $baseUrl = 'https://serpapi.com/search';

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        $apiKey = $this->getCredential('api_key');

        if (! $apiKey) {
            throw new Exception('Missing SerpApi API key');
        }

        $pendingRequest->withHeaders([
            'Accept' => 'application/json',
        ]);
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest()->get($this->baseUrl, [
                'api_key' => $this->getCredential('api_key'),
                'q' => 'test query',
                'engine' => 'google',
                'num' => 1,
            ]);

            return $response->successful();
        } catch (Exception) {
            return false;
        }
    }

    public function searchKeyword(string $query, string $location = 'Hungary', string $language = 'en', int $numResults = 100): array
    {
        $params = [
            'api_key' => $this->getCredential('api_key'),
            'engine' => 'google',
            'q' => $query,
            'location' => $location,
            'hl' => $language,
            'gl' => $this->getCountryCode($location),
            'num' => $numResults,
            'start' => 0,
        ];

        $response = $this->makeRequest()->get($this->baseUrl, $params);

        return $this->handleResponse($response);
    }

    public function getKeywordPosition(string $query, string $targetDomain, string $location = 'Hungary'): ?array
    {
        $searchResults = $this->searchKeyword($query, $location);

        if (! isset($searchResults['organic_results'])) {
            return null;
        }

        foreach ($searchResults['organic_results'] as $index => $result) {
            $resultDomain = $this->extractDomain($result['link'] ?? '');

            if ($this->domainsMatch($resultDomain, $targetDomain)) {
                return [
                    'position' => $index + 1,
                    'url' => $result['link'],
                    'title' => $result['title'] ?? '',
                    'snippet' => $result['snippet'] ?? '',
                    'featured_snippet' => $this->isFeaturedSnippet($searchResults, $result),
                    'serp_features' => $this->extractSerpFeatures($searchResults),
                ];
            }
        }

        return null; // Not found in results
    }

    public function syncKeywordRankings(int $batchSize = 10): int
    {
        $keywords = $this->project->keywords()->get();
        $synced = 0;

        foreach ($keywords->chunk($batchSize) as $keywordChunk) {
            foreach ($keywordChunk as $keyword) {
                try {
                    $this->syncSingleKeyword($keyword);
                    $synced++;

                    // Rate limiting - SerpApi has strict limits
                    sleep(1); // 1 second between requests
                } catch (Exception $e) {
                    Log::warning('Failed to sync keyword: ' . $keyword->keyword, [
                        'error' => $e->getMessage(),
                        'keyword_id' => $keyword->id,
                    ]);
                }
            }

            // Longer pause between batches
            sleep(5);
        }

        return $synced;
    }

    private function syncSingleKeyword(Keyword $keyword): void
    {
        $targetDomain = $this->extractDomain($this->project->url);
        $location = $this->getLocationFromGeoTarget($keyword->geo_target);

        $positionData = $this->getKeywordPosition(
            $keyword->keyword,
            $targetDomain,
            $location
        );

        // Get previous ranking for comparison
        $latestRanking = $keyword->rankings()->latest('checked_at')->first();
        $previousPosition = $latestRanking?->position;

        // Create new ranking record
        Ranking::query()->create([
            'keyword_id' => $keyword->id,
            'position' => $positionData !== null && $positionData !== [] ? $positionData['position'] : null,
            'previous_position' => $previousPosition,
            'url' => $positionData['url'] ?? null,
            'featured_snippet' => $positionData['featured_snippet'] ?? false,
            'serp_features' => $positionData['serp_features'] ?? null,
            'checked_at' => now(),
        ]);
    }

    private function extractDomain(string $url): string
    {
        $parsedUrl = parse_url($url);

        return $parsedUrl['host'] ?? '';
    }

    private function domainsMatch(string $domain1, string $domain2): bool
    {
        // Remove www. prefix for comparison
        $domain1 = preg_replace('/^www\./', '', $domain1);
        $domain2 = preg_replace('/^www\./', '', $domain2);

        return strtolower((string) $domain1) === strtolower((string) $domain2);
    }

    private function isFeaturedSnippet(array $searchResults, array $result): bool
    {
        // Check if this result is in the featured snippet
        if (isset($searchResults['answer_box'])) {
            $answerBoxLink = $searchResults['answer_box']['link'] ?? '';

            return $answerBoxLink === ($result['link'] ?? '');
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function extractSerpFeatures(array $searchResults): array
    {
        $features = [];

        // Check for various SERP features
        if (isset($searchResults['answer_box'])) {
            $features[] = 'featured_snippet';
        }

        if (isset($searchResults['people_also_ask'])) {
            $features[] = 'people_also_ask';
        }

        if (isset($searchResults['images_results'])) {
            $features[] = 'image_pack';
        }

        if (isset($searchResults['videos_results'])) {
            $features[] = 'video_carousel';
        }

        if (isset($searchResults['local_results'])) {
            $features[] = 'local_pack';
        }

        if (isset($searchResults['shopping_results'])) {
            $features[] = 'shopping_results';
        }

        return $features;
    }

    private function getCountryCode(string $location): string
    {
        return match (strtolower($location)) {
            'hungary' => 'hu',
            'united states', 'usa' => 'us',
            'united kingdom', 'uk' => 'gb',
            'germany' => 'de',
            'france' => 'fr',
            default => 'us',
        };
    }

    private function getLocationFromGeoTarget(string $geoTarget): string
    {
        return match (strtolower($geoTarget)) {
            'hu', 'hungary' => 'Hungary',
            'us', 'usa' => 'United States',
            'uk', 'gb' => 'United Kingdom',
            'de', 'germany' => 'Germany',
            'global' => 'United States',
            default => 'Hungary',
        };
    }
}
