<?php

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\Ranking;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GeminiApiService extends BaseApiService
{
    protected string $serviceName = 'gemini';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    protected function configureRequest(PendingRequest $request): void
    {
        $apiKey = $this->getCredential('api_key');
        
        if (!$apiKey) {
            throw new \Exception('Missing Google Gemini API key');
        }

        $request->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    public function testConnection(): bool
    {
        try {
            if (!$this->isConfigured()) {
                return false;
            }
            
            $apiKey = $this->getCredential('api_key');
            
            if (empty($apiKey)) {
                return false;
            }
            
            // Egyszerű direct HTTP teszt
            $client = new \GuzzleHttp\Client();
            $response = $client->get($this->baseUrl . '/models?key=' . $apiKey);
            
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function analyzeSerpResults(string $keyword, array $serpResults): ?array
    {
        $apiKey = $this->getCredential('api_key');
        
        // Készítsük elő a SERP adatokat elemzésre
        $serpData = $this->prepareSerpDataForAnalysis($serpResults);
        
        $prompt = $this->buildAnalysisPrompt($keyword, $serpData);
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 2048,
            ]
        ];

        try {
            $response = $this->makeRequest()->post(
                $this->baseUrl . '/models/gemini-pro:generateContent?key=' . $apiKey,
                $payload
            );
            
            $data = $this->handleResponse($response);
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $analysis = $data['candidates'][0]['content']['parts'][0]['text'];
                return $this->parseAnalysisResponse($analysis);
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API error', [
                'keyword' => $keyword,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function analyzeKeywordPosition(Keyword $keyword): ?array
    {
        // Szerezzük be a SERP adatokat (SerpAPI-ból vagy másik forrásból)
        $serpResults = $this->getSerpResultsForKeyword($keyword);
        
        if (!$serpResults) {
            return null;
        }
        
        return $this->analyzeSerpResults($keyword->keyword, $serpResults);
    }

    private function prepareSerpDataForAnalysis(array $serpResults): array
    {
        $prepared = [
            'organic_results' => [],
            'features' => []
        ];
        
        // Organikus eredmények feldolgozása
        if (isset($serpResults['organic_results'])) {
            foreach (array_slice($serpResults['organic_results'], 0, 20) as $index => $result) {
                $prepared['organic_results'][] = [
                    'position' => $index + 1,
                    'title' => $result['title'] ?? '',
                    'link' => $result['link'] ?? '',
                    'snippet' => $result['snippet'] ?? '',
                    'domain' => parse_url($result['link'] ?? '', PHP_URL_HOST)
                ];
            }
        }
        
        // SERP funkciók feldolgozása
        $features = [];
        if (isset($serpResults['answer_box'])) $features[] = 'featured_snippet';
        if (isset($serpResults['people_also_ask'])) $features[] = 'people_also_ask';
        if (isset($serpResults['images_results'])) $features[] = 'images';
        if (isset($serpResults['local_results'])) $features[] = 'local_pack';
        if (isset($serpResults['shopping_results'])) $features[] = 'shopping';
        
        $prepared['features'] = $features;
        
        return $prepared;
    }

    private function buildAnalysisPrompt(string $keyword, array $serpData): string
    {
        $organicResults = $serpData['organic_results'] ?? [];
        $features = $serpData['features'] ?? [];
        
        $resultsText = "";
        foreach ($organicResults as $result) {
            $resultsText .= "Pozíció {$result['position']}: {$result['title']} - {$result['domain']}\n";
        }
        
        return "Kérem elemezze a következő keresési eredményeket a '{$keyword}' kulcsszóra:\n\n" .
               "SERP funkciók: " . implode(', ', $features) . "\n\n" .
               "Organikus eredmények:\n{$resultsText}\n\n" .
               "Kérem adjon elemzést a következő szempontokból:\n" .
               "1. Versenyhelyzet értékelése (alacsony/közepes/magas)\n" .
               "2. Domináló tartalom típusok\n" .
               "3. Keresési szándék (informational/commercial/transactional)\n" .
               "4. Lehetőségek és kihívások\n" .
               "5. Optimalizálási javaslatok\n\n" .
               "Válaszát JSON formátumban adja meg a következő struktúrával:\n" .
               '{\n' .
               '  "competition_level": "low|medium|high",\n' .
               '  "dominant_content_types": ["blog", "product", "service", "news"],\n' .
               '  "search_intent": "informational|commercial|transactional|navigational",\n' .
               '  "opportunities": ["lehetőség1", "lehetőség2"],\n' .
               '  "challenges": ["kihívás1", "kihívás2"],\n' .
               '  "optimization_tips": ["tipp1", "tipp2"],\n' .
               '  "summary": "Rövid összefoglaló"\n' .
               '}';
    }

    private function parseAnalysisResponse(string $response): ?array
    {
        // Próbáljuk meg kinyerni a JSON-t a válaszból
        if (preg_match('/\{[^{}]*(?:[^{}]*\{[^{}]*\}[^{}]*)*\}/', $response, $matches)) {
            $jsonString = $matches[0];
            $decoded = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Ha nem sikerült JSON-t parseolni, készítsünk egy egyszerű struktúrát
        return [
            'competition_level' => 'medium',
            'dominant_content_types' => ['unknown'],
            'search_intent' => 'informational',
            'opportunities' => ['Analysis could not be parsed'],
            'challenges' => ['Response parsing failed'],
            'optimization_tips' => ['Manual analysis recommended'],
            'summary' => substr($response, 0, 200) . '...',
            'raw_response' => $response
        ];
    }

    private function getSerpResultsForKeyword(Keyword $keyword): ?array
    {
        // SerpAPI-t eltávolítottuk, most használjunk Google Search Console adatokat vagy dummy adatokat teszteléshez
        try {
            // Google Search Console adatok használata helyett
            $rankings = $keyword->rankings()
                ->with(['keyword'])
                ->orderBy('checked_at', 'desc')
                ->limit(10)
                ->get();
                
            if ($rankings->isEmpty()) {
                return null;
            }
            
            // Szimuláljunk SERP adatokat a meglévő ranking adatok alapján
            $organicResults = [];
            foreach ($rankings as $index => $ranking) {
                $organicResults[] = [
                    'title' => 'Eredmény ' . ($index + 1) . ' - ' . $ranking->keyword->keyword,
                    'link' => $ranking->url ?: $this->project->url,
                    'snippet' => 'Leírás a ' . $ranking->keyword->keyword . ' kulcsszóhoz, pozíció: ' . $ranking->position,
                    'position' => $ranking->position
                ];
            }
            
            return [
                'organic_results' => $organicResults,
                'search_metadata' => [
                    'keyword' => $keyword->keyword,
                    'location' => $keyword->geo_target ?: 'Hungary'
                ]
            ];
        } catch (\Exception $e) {
            Log::warning('Could not get search data for Gemini analysis', [
                'keyword' => $keyword->keyword,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
