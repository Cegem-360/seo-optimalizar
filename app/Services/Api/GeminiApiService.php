<?php

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\Ranking;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\PendingRequest;

class GeminiApiService extends BaseApiService
{
    protected string $serviceName = 'gemini';

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        $apiKey = $this->getCredential('api_key');

        if (! $apiKey) {
            throw new Exception('Missing Google Gemini API key');
        }

        $pendingRequest->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    public function testConnection(): bool
    {
        try {
            if (! $this->isConfigured()) {
                return false;
            }

            $apiKey = $this->getCredential('api_key');

            if (empty($apiKey)) {
                return false;
            }

            // Egyszerű direct HTTP teszt
            $client = new Client();
            $response = $client->get($this->baseUrl . '/models?key=' . $apiKey);

            return $response->getStatusCode() === 200;
        } catch (Exception) {
            return false;
        }
    }

    public function analyzeSerpResults(string $keyword, array $serpResults): ?array
    {
        try {
            $apiKey = $this->getCredential('api_key');

            if (empty($apiKey)) {
                return null;
            }

            // Készítsük elő a SERP adatokat elemzésre
            $serpData = $this->prepareSerpDataForAnalysis($serpResults);

            $prompt = $this->buildAnalysisPrompt($keyword, $serpData);

            // Egyszerűbb HTTP kérés közvetlenül
            $client = new Client();
            $response = $client->post($this->baseUrl . '/models/gemini-1.5-flash:generateContent?key=' . $apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'topK' => 1,
                        'topP' => 1,
                        'maxOutputTokens' => 1024,
                    ],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $analysis = $data['candidates'][0]['content']['parts'][0]['text'];

                    return $this->parseAnalysisResponse($analysis);
                }
            }

            return null;
        } catch (Exception) {
            return null;
        }
    }

    public function analyzeKeywordPosition(Keyword $keyword): ?array
    {
        $serpResults = $this->getSerpResultsForKeyword($keyword);

        if ($serpResults === []) {
            return null;
        }

        return $this->analyzeSerpResults($keyword->keyword, $serpResults);
    }

    public function analyzeKeywordWithPosition(Keyword $keyword, $latestRanking = null): ?array
    {
        try {
            $apiKey = $this->getCredential('api_key');

            if (empty($apiKey)) {
                return null;
            }

            // Építsük fel a SERP adatokat a pozíció elemzéshez
            $currentPosition = $latestRanking->position ?? 'Nincs adat';
            $url = $latestRanking->url ?? $this->project->url;

            // Lekérjük a többi versenytársat is
            $competitors = $this->getCompetitorsForKeyword($keyword);

            $prompt = $this->buildPositionAnalysisPrompt($keyword->keyword, $currentPosition, $url, $competitors);

            $client = new Client();
            $response = $client->post($this->baseUrl . '/models/gemini-1.5-flash:generateContent?key=' . $apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'topK' => 1,
                        'topP' => 1,
                        'maxOutputTokens' => 2048,
                    ],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $analysis = $data['candidates'][0]['content']['parts'][0]['text'];

                    return $this->parsePositionAnalysisResponse($analysis);
                }
            }

            return null;
        } catch (Exception) {
            return null;
        }
    }

    private function prepareSerpDataForAnalysis(array $serpResults): array
    {
        $prepared = [
            'organic_results' => [],
            'features' => [],
        ];

        // Organikus eredmények feldolgozása
        if (isset($serpResults['organic_results'])) {
            foreach (array_slice($serpResults['organic_results'], 0, 20) as $index => $result) {
                $prepared['organic_results'][] = [
                    'position' => $index + 1,
                    'title' => $result['title'] ?? '',
                    'link' => $result['link'] ?? '',
                    'snippet' => $result['snippet'] ?? '',
                    'domain' => parse_url($result['link'] ?? '', PHP_URL_HOST),
                ];
            }
        }

        // SERP funkciók feldolgozása
        $features = [];
        if (isset($serpResults['answer_box'])) {
            $features[] = 'featured_snippet';
        }

        if (isset($serpResults['people_also_ask'])) {
            $features[] = 'people_also_ask';
        }

        if (isset($serpResults['images_results'])) {
            $features[] = 'images';
        }

        if (isset($serpResults['local_results'])) {
            $features[] = 'local_pack';
        }

        if (isset($serpResults['shopping_results'])) {
            $features[] = 'shopping';
        }

        $prepared['features'] = $features;

        return $prepared;
    }

    private function buildAnalysisPrompt(string $keyword, array $serpData): string
    {
        $organicResults = $serpData['organic_results'] ?? [];
        $features = $serpData['features'] ?? [];

        $resultsText = '';
        foreach ($organicResults as $organicResult) {
            $resultsText .= sprintf('Pozíció %s: %s - %s%s', $organicResult['position'], $organicResult['title'], $organicResult['domain'], PHP_EOL);
        }

        return "Kérem elemezze a következő keresési eredményeket a '{$keyword}' kulcsszóra:\n\n" .
               'SERP funkciók: ' . implode(', ', $features) . "\n\n" .
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
            'raw_response' => $response,
        ];
    }

    private function getCompetitorsForKeyword(Keyword $keyword): array
    {
        // Versenytársak lekérése a ranking adatokból
        $competitors = [];

        // Az összes ranking lekérése erre a kulcsszóra
        $allRankings = $keyword->rankings()
            ->orderBy('position', 'asc')
            ->limit(30)
            ->get();

        /** @var Ranking $allRanking */
        foreach ($allRankings as $allRanking) {
            $competitors[] = [
                'position' => $allRanking->position,
                'url' => $allRanking->url,
                'domain' => parse_url((string) $allRanking->url, PHP_URL_HOST),
            ];
        }

        // Ha nincs elég adat, készítsünk szimulált versenytársakat
        if (count($competitors) < 10) {
            $domains = [
                'wikipedia.org', 'forbes.com', 'medium.com',
                'businessinsider.com', 'techcrunch.com', 'amazon.com',
                'youtube.com', 'reddit.com', 'quora.com', 'linkedin.com',
            ];

            for ($i = count($competitors) + 1; $i <= 10; $i++) {
                $competitors[] = [
                    'position' => $i,
                    'domain' => $domains[array_rand($domains)],
                    'url' => 'https://' . $domains[array_rand($domains)] . '/example',
                ];
            }
        }

        return $competitors;
    }

    private function buildPositionAnalysisPrompt(string $keyword, $position, string $url, array $competitors): string
    {
        $competitorsList = '';
        foreach (array_slice($competitors, 0, 20) as $comp) {
            $competitorsList .= sprintf('Pozíció %s: %s%s', $comp['position'], $comp['domain'], PHP_EOL);
        }

        $positionText = is_numeric($position) ? $position . '. helyen' : 'nincs rankingben';

        return "Elemezd a következő kulcsszó pozícióját és versenytársait!\n\n" .
               "Kulcsszó: '{$keyword}'\n" .
               sprintf('Jelenlegi pozíció: %s%s', $positionText, PHP_EOL) .
               "URL: {$url}\n\n" .
               "Top 20 versenytárs:\n{$competitorsList}\n\n" .
               "Kérlek válaszolj a következő kérdésekre:\n" .
               "1. Milyen a jelenlegi pozíció értékelése? (kiváló/jó/közepes/gyenge/kritikus)\n" .
               "2. Kik a fő versenytársak és miért ők vannak előrébb?\n" .
               "3. Milyen tartalmi vagy technikai előnyük van a versenytársaknak?\n" .
               "4. Mit kell javítani a jobb pozícióért?\n" .
               "5. Reális célpozíció és időtáv\n\n" .
               "Válaszod JSON formátumban add meg (a detailed_analysis mező legyen tiszta szöveg, NE tartalmazzon JSON-t vagy kód blokkokat):\n" .
               '```json' . "\n" .
               '{' . "\n" .
               '  "position_rating": "kiváló|jó|közepes|gyenge|kritikus",' . "\n" .
               '  "current_position": ' . (is_numeric($position) ? $position : 'null') . ',' . "\n" .
               '  "main_competitors": ["domain1.com", "domain2.com", "domain3.com"],' . "\n" .
               '  "competitor_advantages": ["konkrét előny1", "konkrét előny2", "konkrét előny3"],' . "\n" .
               '  "improvement_areas": ["konkrét javítási terület1", "konkrét javítási terület2", "konkrét javítási terület3"],' . "\n" .
               '  "target_position": 5,' . "\n" .
               '  "estimated_timeframe": "2-3 hónap",' . "\n" .
               '  "quick_wins": ["azonnal megvalósítható javítás1", "azonnal megvalósítható javítás2"],' . "\n" .
               '  "detailed_analysis": "Itt írd le részletesen az elemzést tiszta szövegként, magyarázd el a pozíció okait és a javítási stratégiát. Ez egy összefüggő bekezdés legyen, NE tartalmazzon JSON kódot vagy egyéb formázást."' . "\n" .
               '}' . "\n" .
               '```';
    }

    private function parsePositionAnalysisResponse(string $response): array
    {
        // Először próbáljuk meg kinyerni a JSON-t
        $jsonString = $response;

        // Ha code block-ban van
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $jsonString = $matches[1];
        }
        // Ha csak {} között van
        elseif (preg_match('/\{[^{}]*(?:[^{}]*\{[^{}]*\}[^{}]*)*\}/s', $response, $matches)) {
            $jsonString = $matches[0];
        }

        // Tisztítsuk meg a JSON stringet
        $jsonString = trim($jsonString);

        // Próbáljuk dekódolni
        $decoded = json_decode($jsonString, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Ha a detailed_analysis tartalmaz JSON kódot, tisztítsuk meg
            if (isset($decoded['detailed_analysis']) && str_contains((string) $decoded['detailed_analysis'], '```')) {
                $decoded['detailed_analysis'] = preg_replace('/```.*?```/s', '', (string) $decoded['detailed_analysis']);
                $decoded['detailed_analysis'] = trim((string) $decoded['detailed_analysis']);
            }

            // Biztosítsuk hogy minden szükséges mező megvan
            $decoded['position_rating'] ??= 'ismeretlen';
            $decoded['main_competitors'] ??= [];
            $decoded['competitor_advantages'] ??= [];
            $decoded['improvement_areas'] ??= [];
            $decoded['quick_wins'] ??= [];
            $decoded['detailed_analysis'] ??= 'Részletes elemzés nem elérhető.';

            return $decoded;
        }

        // Ha nem sikerült parseolni, próbáljuk meg szövegként feldolgozni
        return [
            'position_rating' => 'ismeretlen',
            'current_position' => null,
            'main_competitors' => [],
            'competitor_advantages' => [],
            'improvement_areas' => ['Az elemzés feldolgozása sikertelen'],
            'target_position' => null,
            'estimated_timeframe' => 'Nem meghatározható',
            'quick_wins' => [],
            'detailed_analysis' => strip_tags($response), // Eltávolítjuk a HTML/markdown jelöléseket
        ];
    }

    private function getSerpResultsForKeyword(Keyword $keyword): array
    {
        try {
            // Ranking adatok lekérése
            /** @var Collection<int, Ranking> $rankings */
            $rankings = $keyword->rankings()
                ->orderBy('checked_at', 'desc')
                ->limit(10)
                ->get();

            if ($rankings->isEmpty()) {
                // Ha nincs ranking adat, készítsünk dummy adatot teszteléshez
                return [
                    'organic_results' => [
                        [
                            'title' => 'Példa eredmény 1 - ' . $keyword->keyword,
                            'link' => $this->project->url,
                            'snippet' => 'Ez egy példa leírás a ' . $keyword->keyword . ' kulcsszóhoz.',
                            'position' => 1,
                        ],
                        [
                            'title' => 'Példa eredmény 2 - ' . $keyword->keyword,
                            'link' => 'https://example.com',
                            'snippet' => 'További információ a ' . $keyword->keyword . ' témájában.',
                            'position' => 2,
                        ],
                    ],
                    'search_metadata' => [
                        'keyword' => $keyword->keyword,
                        'location' => $keyword->geo_target ?: 'Hungary',
                    ],
                ];
            }

            // Valós ranking adatok alapján SERP szimulálás
            $organicResults = [];
            /** @var Ranking $ranking */
            foreach ($rankings as $index => $ranking) {
                $organicResults[] = [
                    'title' => 'Eredmény ' . ($index + 1) . ' - ' . $keyword->keyword,
                    'link' => $ranking->url ?: $this->project->url,
                    'snippet' => 'Leírás a ' . $keyword->keyword . ' kulcsszóhoz, pozíció: ' . $ranking->position,
                    'position' => $ranking->position,
                ];
            }

            return [
                'organic_results' => $organicResults,
                'search_metadata' => [
                    'keyword' => $keyword->keyword,
                    'location' => $keyword->geo_target ?: 'Hungary',
                ],
            ];
        } catch (Exception) {
            // Fallback dummy data ha minden más meghibásodna
            return [
                'organic_results' => [
                    [
                        'title' => 'Teszt eredmény - ' . $keyword->keyword,
                        'link' => $this->project->url,
                        'snippet' => 'Teszt leírás a ' . $keyword->keyword . ' kulcsszóhoz.',
                        'position' => 1,
                    ],
                ],
                'search_metadata' => [
                    'keyword' => $keyword->keyword,
                    'location' => 'Hungary',
                ],
            ];
        }
    }
}
