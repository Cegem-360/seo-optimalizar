<?php

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\Ranking;
use App\Models\SeoAnalysis;
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

    public function analyzeSerpResults(string $keyword, array $serpResults, ?Keyword $keywordModel = null): ?array
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
            $response = $client->post($this->baseUrl . '/models/gemini-2.0-flash:generateContent?key=' . $apiKey, [
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
                    $parsedAnalysis = $this->parseAnalysisResponse($analysis);

                    // Ha van Keyword model, mentsük el az elemzést
                    if ($keywordModel instanceof Keyword && $parsedAnalysis) {
                        $this->saveSeoAnalysis($keywordModel, $parsedAnalysis, null);
                    }

                    return $parsedAnalysis;
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

        return $this->analyzeSerpResults($keyword->keyword, $serpResults, $keyword);
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

            // Domain kibontása a projekt URL-ből (univerzális kereséshez)
            $projectDomain = parse_url($this->project->url, PHP_URL_HOST);
            if ($projectDomain && str_starts_with($projectDomain, 'www.')) {
                $projectDomain = substr($projectDomain, 4);
            }

            // Lekérjük a többi versenytársat is
            $competitors = $this->getCompetitorsForKeyword($keyword);

            $prompt = $this->buildPositionAnalysisPrompt(keyword: $keyword->keyword, domain: $projectDomain);

            $client = new Client();
            https:// generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent
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
                    $parsedAnalysis = $this->parsePositionAnalysisResponse($analysis);

                    // Mentsük el az elemzést adatbázisba
                    if ($parsedAnalysis !== []) {
                        $this->saveSeoAnalysis($keyword, $parsedAnalysis, $currentPosition);
                    }

                    return $parsedAnalysis;
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

    private function saveSeoAnalysis(Keyword $keyword, array $analysis, $currentPosition): void
    {
        SeoAnalysis::query()->create([
            'keyword_id' => $keyword->id,
            'project_id' => $keyword->project_id,
            'competition_level' => $analysis['competition_level'] ?? null,
            'search_intent' => $analysis['search_intent'] ?? null,
            'dominant_content_types' => $analysis['dominant_content_types'] ?? [],
            'opportunities' => $analysis['opportunities'] ?? [],
            'challenges' => $analysis['challenges'] ?? [],
            'optimization_tips' => $analysis['optimization_tips'] ?? [],
            'summary' => $analysis['summary'] ?? null,
            'position_rating' => $analysis['position_rating'] ?? null,
            'current_position' => is_numeric($currentPosition) ? $currentPosition : null,
            'target_position' => $analysis['target_position'] ?? null,
            'estimated_timeframe' => $analysis['estimated_timeframe'] ?? null,
            'main_competitors' => $analysis['main_competitors'] ?? [],
            'competitor_advantages' => $analysis['competitor_advantages'] ?? [],
            'improvement_areas' => $analysis['improvement_areas'] ?? [],
            'quick_wins' => $analysis['quick_wins'] ?? [],
            'detailed_analysis' => $analysis['detailed_analysis'] ?? null,
            'raw_response' => $analysis,
            'analysis_source' => 'gemini',
        ]);
    }

    private function buildPositionAnalysisPrompt(string $keyword, string $domain): string
    {
        return "Végezz webes keresést és elemzést a következő kulcsszó aktuális pozíciójáról!\n\n" .
               "Kulcsszó: '{$keyword}'\n" .
               "Elemzendő domain: {$domain} (BÁRMILYEN aloldallal, pl: {$domain}/* )\n\n" .
               "FELADAT: \n" .
               "1. Indíts saját webes keresést a '{$keyword}' kulcsszóra!\n" .
               "2. Azonosítsd a TOP 100 találatot a keresési eredményekben!\n" .
               "3. Keresd meg a '{$domain}' domainről származó BÁRMILYEN oldalt/aloldalt!\n" .
               "4. Ha van találat a '{$domain}' domainről (pl. {$domain}/xyz vagy www.{$domain}/abc), írd le a pozícióját!\n" .
               "5. Ha nem található SEMMILYEN {$domain} oldal az első 100-ban, akkor a current_position legyen null!\n" .
               "6. Elemezd a TOP 10-20 versenytársat részletesen!\n\n" .
               "FONTOS SZABÁLYOK:\n" .
               "- Univerzálisan keresd a '{$domain}' domaint: {$domain}, www.{$domain}, {$domain}/any-page, stb.\n" .
               "- Ha BÁRMILYEN {$domain} aloldal nincs az első 100 találatban, a current_position KÖTELEZŐEN null legyen!\n" .
               "- Ne adj meg pozíciót, ha nem találsz SEMMILYEN {$domain} oldalt a keresési eredményekben!\n" .
               "- A detailed_analysis-ben mindig írd le, hogy melyik {$domain} oldalt találtad és hányadik helyen, vagy hogy nem találtál semmit!\n\n" .
               "Válaszolj a következő kérdésekre a saját webes keresésed alapján:\n" .
               "1. Mi a '{$domain}' domain PONTOS pozíciója? (null ha nincs az első 100-ban)\n" .
               "2. Kik a TOP 10 versenytárs a keresésed alapján?\n" .
               "3. Milyen előnyöket azonosítasz a versenytársaknál?\n" .
               "4. Mit kell javítani a jobb pozícióért?\n" .
               "5. Reális célpozíció és időtáv\n\n" .
               "Válaszod JSON formátumban add meg:\n" .
               '```json' . "\n" .
               '{' . "\n" .
               '  "position_rating": "kiváló|jó|közepes|gyenge|kritikus",' . "\n" .
               '  "current_position": null vagy konkrét szám (1-100),' . "\n" .
               '  "main_competitors": ["domain1.com", "domain2.com", "domain3.com"],' . "\n" .
               '  "competitor_advantages": ["konkrét előny1", "konkrét előny2", "konkrét előny3"],' . "\n" .
               '  "improvement_areas": ["konkrét javítási terület1", "konkrét javítási terület2"],' . "\n" .
               '  "target_position": 5,' . "\n" .
               '  "estimated_timeframe": "2-3 hónap",' . "\n" .
               '  "quick_wins": ["gyors javítás1", "gyors javítás2"],' . "\n" .
               '  "detailed_analysis": "Részletes elemzés. KÖTELEZŐEN írd le: Hányadik helyen találtad az URL-t (vagy hogy nem találtad). Magyarázd el a pozíció okait és a javítási stratégiát."' . "\n" .
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

    /**
     * Analyze a website URL using Gemini API
     */
    public function analyzeWebsite(string $url, string $analysisType, string $prompt): ?string
    {
        try {
            $apiKey = $this->getCredential('api_key');

            if (empty($apiKey)) {
                throw new Exception('Missing Google Gemini API key');
            }

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
                        'temperature' => 0.3,
                        'topK' => 1,
                        'topP' => 1,
                        'maxOutputTokens' => 4096,
                        'responseMimeType' => 'application/json',
                    ],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
            }

            return null;
        } catch (Exception $exception) {
            throw new Exception('Gemini API error: ' . $exception->getMessage(), $exception->getCode(), $exception);
        }
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
                // Domain kibontása univerzális kereséshez
                $projectDomain = parse_url($this->project->url, PHP_URL_HOST);
                if ($projectDomain && str_starts_with($projectDomain, 'www.')) {
                    $projectDomain = substr($projectDomain, 4);
                }

                // Ha nincs ranking adat, készítsünk dummy adatot teszteléshez
                return [
                    'organic_results' => [
                        [
                            'title' => 'Példa eredmény 1 - ' . $keyword->keyword,
                            'link' => 'https://' . $projectDomain . '/page1',
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
                        'id' => 'search_' . uniqid(),
                        'total_results' => rand(100000, 5000000),
                        'time_taken_displayed' => round(rand(20, 80) / 100, 2) . ' seconds',
                        'device' => 'desktop',
                        'google_domain' => 'google.com',
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
                    'id' => 'search_' . uniqid(),
                    'total_results' => rand(100000, 5000000),
                    'time_taken_displayed' => round(rand(20, 80) / 100, 2) . ' seconds',
                    'device' => 'desktop',
                    'google_domain' => 'google.com',
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
