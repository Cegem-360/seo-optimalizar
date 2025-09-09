<?php

namespace App\Services\Api;

use App\Models\Keyword;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

class SemrushApiService extends BaseApiService
{
    protected string $serviceName = 'semrush';

    private string $baseUrl = 'https://api.semrush.com';

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        $apiKey = $this->getCredential('api_key');

        if (! $apiKey) {
            throw new \Exception('Missing SEMrush API key');
        }

        $pendingRequest->withHeaders([
            'Accept' => 'application/json',
        ]);
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest()->get($this->baseUrl . '/', [
                'key' => $this->getCredential('api_key'),
                'type' => 'phrase_this',
                'phrase' => 'test',
                'database' => 'us',
                'display_limit' => 1,
            ]);

            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    public function getKeywordData(string $keyword, string $database = 'us'): ?array
    {
        try {
            $response = $this->makeRequest()->get($this->baseUrl . '/', [
                'key' => $this->getCredential('api_key'),
                'type' => 'phrase_this',
                'phrase' => $keyword,
                'database' => $database,
                'export_columns' => 'Ph,Nq,Cp,Co,Nr,Td',
            ]);

            $data = $this->handleResponse($response);

            if ($data !== []) {
                // Parse CSV-like response
                $lines = explode("\n", trim($data));
                if (count($lines) >= 2) {
                    $values = str_getcsv($lines[1], ';');

                    return [
                        'keyword' => $values[0] ?? $keyword,
                        'search_volume' => (int) ($values[1] ?? 0),
                        'cpc' => (float) ($values[2] ?? 0),
                        'competition' => (float) ($values[3] ?? 0),
                        'results' => (int) ($values[4] ?? 0),
                        'difficulty' => $this->calculateDifficulty($values[3] ?? 0, $values[4] ?? 0),
                    ];
                }
            }

            return null;
        } catch (\Exception $exception) {
            \Illuminate\Support\Facades\Log::error('SEMrush API error', [
                'keyword' => $keyword,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function bulkGetKeywordData(Collection $keywords, string $database = 'us'): array
    {
        $results = [];

        foreach ($keywords as $keyword) {
            $keywordText = $keyword instanceof Keyword ? $keyword->keyword : $keyword;
            $data = $this->getKeywordData($keywordText, $database);

            if ($data !== null && $data !== []) {
                $results[$keywordText] = $data;
            }

            // Rate limiting - SEMrush allows 1 request per second for free accounts
            sleep(1);
        }

        return $results;
    }

    public function updateKeywordMetrics(Keyword $keyword, string $database = 'us'): bool
    {
        $data = $this->getKeywordData($keyword->keyword, $this->getDatabaseFromGeoTarget($keyword->geo_target));

        if ($data === null || $data === []) {
            return false;
        }

        $keyword->update([
            'search_volume' => $data['search_volume'],
            'difficulty_score' => $data['difficulty'],
        ]);

        return true;
    }

    public function updateProjectKeywords(int $batchSize = 50): int
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
                sleep(1);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to update keyword metrics', [
                    'keyword_id' => $keyword->id,
                    'keyword' => $keyword->keyword,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $updated;
    }

    private function calculateDifficulty(float $competition, int $results): int
    {
        // Simple difficulty calculation based on competition and result count
        $difficultyScore = 0;

        // Competition score (0-1) contributes 60% to difficulty
        $difficultyScore += $competition * 60;

        // Result count contributes 40% (normalized)
        if ($results > 0) {
            $normalizedResults = min($results / 1000000, 1); // Normalize to 0-1
            $difficultyScore += $normalizedResults * 40;
        }

        return min(100, max(1, (int) round($difficultyScore)));
    }

    private function getDatabaseFromGeoTarget(string $geoTarget): string
    {
        return match (strtolower($geoTarget)) {
            'hu', 'hungary' => 'hu',
            'us', 'usa' => 'us',
            'uk', 'gb' => 'uk',
            'de', 'germany' => 'de',
            'fr', 'france' => 'fr',
            'global' => 'us',
            default => 'us',
        };
    }
}
