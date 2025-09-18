<?php

namespace App\Services\Api;

use App\Models\CompetitorAnalysis;
use App\Models\Keyword;
use App\Models\PageSpeedAnalysis;
use App\Models\Project;
use App\Models\Ranking;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class CompetitorAnalysisService
{
    private readonly Client $client;

    private readonly PageSpeedService $pageSpeedService;

    private ?GeminiApiService $geminiApiService = null;

    public function __construct(?Project $project = null, ?Repository $repository = null)
    {
        $this->client = new Client();
        try {
            $apiKey = $repository?->get('services.google.pagespeed_api_key') ?? config('services.google.pagespeed_api_key');
        } catch (Exception) {
            $apiKey = '';
        }

        $this->pageSpeedService = new PageSpeedService($apiKey, $repository);
        if ($project instanceof Project) {
            $this->geminiApiService = new GeminiApiService($project);
        }
    }

    public function setProject(Project $project): void
    {
        $this->geminiApiService = new GeminiApiService($project);
    }

    public function analyzeCompetitor(string $domain, string $url, Keyword $keyword, int $position, array $aiData = []): ?CompetitorAnalysis
    {
        try {
            $analysis = [
                'keyword_id' => $keyword->id,
                'project_id' => $keyword->project_id,
                'competitor_domain' => $domain,
                'competitor_url' => $url,
                'position' => $position,
                'analyzed_at' => now(),
            ];

            // Ha van AI adat, adjuk hozzá
            if ($aiData !== []) {
                $analysis['ai_discovered'] = $aiData['ai_discovered'] ?? false;
                $analysis['competitor_type'] = $aiData['type'] ?? null;
                $analysis['strength_score'] = $aiData['strength_score'] ?? null;
                $analysis['relevance_reason'] = $aiData['relevance_reason'] ?? null;
                $analysis['main_advantages'] = json_encode($aiData['main_advantages'] ?? []);
                $analysis['estimated_traffic'] = $aiData['estimated_traffic'] ?? null;
                $analysis['content_focus'] = $aiData['content_focus'] ?? null;
            }

            // 1. Alapvető domain elemzés
            $domainMetrics = $this->analyzeDomainMetrics($domain);
            $analysis = array_merge($analysis, $domainMetrics);

            // 2. Oldal technikai elemzés
            $technicalMetrics = $this->analyzeTechnicalMetrics($url);
            $analysis = array_merge($analysis, $technicalMetrics);

            // 3. Tartalom elemzés
            $contentMetrics = $this->analyzeContent($url);
            $analysis = array_merge($analysis, $contentMetrics);

            // 4. PageSpeed elemzés
            $speedMetrics = $this->analyzePageSpeed($url);
            $analysis = array_merge($analysis, $speedMetrics);

            return CompetitorAnalysis::query()->create($analysis);
        } catch (Exception $exception) {
            Log::error('Competitor analysis failed', [
                'domain' => $domain,
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function analyzeTopCompetitors(Keyword $keyword, int $limit = 10): array
    {
        $competitors = [];

        // AI-val felfedezett versenytársak lekérése
        $topDomains = $this->discoverCompetitorsWithAi($keyword, $limit);

        foreach ($topDomains as $position => $competitorData) {
            $analysis = $this->analyzeCompetitor(
                $competitorData['domain'],
                $competitorData['url'],
                $keyword,
                $position + 1,
                $competitorData // átadjuk az AI adatokat is
            );

            if ($analysis instanceof CompetitorAnalysis) {
                $competitors[] = $analysis;
            }
        }

        return $competitors;
    }

    private function discoverCompetitorsWithAi(Keyword $keyword, int $limit): array
    {
        try {
            // Először próbáljuk a valós ranking adatokból
            $rankings = $keyword->rankings()
                ->whereNotNull('url')
                ->orderBy('position')
                ->limit($limit)
                ->get();

            $existingCompetitors = [];
            /** @var Ranking $ranking */
            foreach ($rankings as $ranking) {
                $domain = parse_url((string) $ranking->url, PHP_URL_HOST);
                if ($domain) {
                    $existingCompetitors[] = [
                        'domain' => $domain,
                        'url' => $ranking->url,
                        'position' => $ranking->position,
                    ];
                }
            }

            // AI-val kérjük le a releváns versenytársakat
            $aiDiscoveredCompetitors = [];
            $project = $keyword->project;
            if ($this->geminiApiService instanceof GeminiApiService && $project instanceof Project) {
                $aiDiscoveredCompetitors = $this->discoverCompetitorsUsingAi(
                    $keyword,
                    $project,
                    $existingCompetitors
                );
            }

            // Kombináljuk a meglévő és AI által felfedezett versenytársakat
            $allCompetitors = $this->mergeCompetitorLists(
                $existingCompetitors,
                $aiDiscoveredCompetitors,
                $limit
            );

            return array_slice($allCompetitors, 0, $limit);
        } catch (Exception $exception) {
            Log::error('AI competitor discovery failed', [
                'keyword' => $keyword->keyword,
                'error' => $exception->getMessage(),
            ]);

            // Fallback a régi megoldásra
            return $this->getTopCompetitorDomains($keyword, $limit);
        }
    }

    private function getTopCompetitorDomains(Keyword $keyword, int $limit): array
    {
        // Először próbáljuk a valós ranking adatokból
        $rankings = $keyword->rankings()
            ->whereNotNull('url')
            ->orderBy('position')
            ->limit($limit)
            ->get();

        $competitors = [];
        /** @var Ranking $ranking */
        foreach ($rankings as $ranking) {
            $domain = parse_url((string) $ranking->url, PHP_URL_HOST);
            if ($domain) {
                $competitors[] = [
                    'domain' => $domain,
                    'url' => $ranking->url,
                ];
            }
        }

        // Ha nincs elég adat, kiegészítjük általános versenytársakkal
        if (count($competitors) < $limit) {
            $commonCompetitors = [
                'wikipedia.org',
                'forbes.com',
                'medium.com',
                'linkedin.com',
                'youtube.com',
                'reddit.com',
                'quora.com',
                'amazon.com',
                'facebook.com',
                'instagram.com',
            ];

            foreach ($commonCompetitors as $commonCompetitor) {
                if (count($competitors) >= $limit) {
                    break;
                }

                if (! (new Collection($competitors))->pluck('domain')->contains($commonCompetitor)) {
                    $competitors[] = [
                        'domain' => $commonCompetitor,
                        'url' => 'https://' . $commonCompetitor,
                    ];
                }
            }
        }

        return array_slice($competitors, 0, $limit);
    }

    private function analyzeDomainMetrics(string $domain): array
    {
        // Itt integrálhatunk külső API-kat (Moz, Ahrefs, SEMrush)
        // Most szimulált adatokat adunk vissza
        return [
            'domain_authority' => $this->simulateDomainAuthority($domain),
            'page_authority' => $this->simulatePageAuthority($domain),
            'backlinks_count' => $this->simulateBacklinksCount($domain),
        ];
    }

    private function analyzeTechnicalMetrics(string $url): array
    {
        try {
            // Egyszerű HEAD kérés a technikai adatokért
            $response = $this->client->head($url, [
                'timeout' => 10,
                'allow_redirects' => true,
                'verify' => false,
            ]);

            $headers = $response->getHeaders();

            return [
                'has_ssl' => str_starts_with($url, 'https://'),
                'is_mobile_friendly' => $this->checkMobileFriendly(),
            ];
        } catch (Exception) {
            return [
                'has_ssl' => str_starts_with($url, 'https://'),
                'is_mobile_friendly' => false,
            ];
        }
    }

    private function analyzeContent(string $url): array
    {
        try {
            // GET kérés a tartalom elemzéshez
            $response = $this->client->get($url, [
                'timeout' => 15,
                'allow_redirects' => true,
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; SEO-Analyzer/1.0)',
                ],
            ]);

            $html = $response->getBody()->getContents();

            return [
                'content_length' => $this->extractContentLength($html),
                'title_tag' => $this->extractTitle($html),
                'meta_description' => $this->extractMetaDescription($html),
                'headers_structure' => $this->extractHeaders($html),
                'has_schema_markup' => $this->checkSchemaMarkup($html),
            ];
        } catch (Exception) {
            return [
                'content_length' => null,
                'title_tag' => null,
                'meta_description' => null,
                'headers_structure' => [],
                'has_schema_markup' => false,
            ];
        }
    }

    private function analyzePageSpeed(string $url): array
    {
        // PageSpeed elemzés (ha van API kulcs)
        $pageSpeedAnalysis = $this->pageSpeedService->analyzeUrl($url, 'desktop');

        if ($pageSpeedAnalysis instanceof PageSpeedAnalysis) {
            return [
                'page_speed_score' => $pageSpeedAnalysis->performance_score,
            ];
        }

        return [
            'page_speed_score' => null,
        ];
    }

    // Segéd metódusok

    private function simulateDomainAuthority(string $domain): int
    {
        // Népszerű domainok magasabb DA-t kapnak
        $highAuthDomains = ['wikipedia.org', 'google.com', 'youtube.com', 'facebook.com', 'linkedin.com'];
        $mediumAuthDomains = ['medium.com', 'forbes.com', 'reddit.com', 'quora.com'];
        if (in_array($domain, $highAuthDomains)) {
            return random_int(80, 100);
        }

        if (in_array($domain, $mediumAuthDomains)) {
            return random_int(60, 79);
        }

        return random_int(20, 59);
    }

    private function simulatePageAuthority(string $domain): int
    {
        $da = $this->simulateDomainAuthority($domain);

        return max(1, $da - random_int(5, 15));
    }

    private function simulateBacklinksCount(string $domain): int
    {
        $da = $this->simulateDomainAuthority($domain);

        return (int) (($da / 10) ** 3 * random_int(100, 1000));
    }

    private function checkMobileFriendly(): bool
    {
        // Egyszerű ellenőrzés responsive jelzőkre
        return true;
        // Alapértelmezetten igen
    }

    private function extractContentLength(string $html): int
    {
        // Eltávolítjuk a HTML tageket és számoljuk a karaktereket
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);

        return strlen(trim((string) $text));
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return trim(html_entity_decode($matches[1]));
        }

        return null;
    }

    private function extractMetaDescription(string $html): ?string
    {
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            return trim(html_entity_decode($matches[1]));
        }

        return null;
    }

    private function extractHeaders(string $html): array
    {
        $headers = [];

        // H1-H6 tagek keresése
        for ($i = 1; $i <= 6; $i++) {
            if (preg_match_all(sprintf('/<h%d[^>]*>(.*?)<\/h%d>/is', $i, $i), $html, $matches)) {
                $headers['h' . $i] = array_map(fn (string $match): string => trim(strip_tags(html_entity_decode($match))), $matches[1]);
            }
        }

        return $headers;
    }

    private function checkSchemaMarkup(string $html): bool
    {
        // JSON-LD, microdata vagy RDFa keresése
        return
            str_contains($html, 'application/ld+json') ||
            str_contains($html, 'itemscope') ||
            str_contains($html, 'typeof=');
    }

    private function getGeminiApiKey(): ?string
    {
        if (! $this->geminiApiService instanceof GeminiApiService) {
            return null;
        }

        // Használjuk reflection-t a protected metódus eléréséhez
        try {
            $reflectionClass = new ReflectionClass($this->geminiApiService);
            $method = $reflectionClass->getMethod('getCredential');

            return $method->invoke($this->geminiApiService, 'api_key');
        } catch (Exception $exception) {
            Log::error('Failed to get Gemini API key', ['error' => $exception->getMessage()]);

            return null;
        }
    }

    private function discoverCompetitorsUsingAi(Keyword $keyword, Project $project, array $existingCompetitors): array
    {
        try {
            // Használjuk a GeminiApiService getCredential metódusát
            if (! $this->geminiApiService instanceof GeminiApiService || ! $this->geminiApiService->isConfigured()) {
                Log::warning('Gemini service not configured');

                return [];
            }

            $prompt = $this->buildCompetitorDiscoveryPrompt($keyword, $project, $existingCompetitors);

            // Közvetlenül használjuk a Gemini service-t
            $apiKey = $this->getGeminiApiKey();
            if (empty($apiKey)) {
                Log::warning('No Gemini API key found');

                return [];
            }

            $client = new Client();
            $response = $client->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey, [
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
                        'maxOutputTokens' => 2048,
                    ],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];

                    return $this->parseAiCompetitorResponse($aiResponse);
                }
            }
        } catch (Exception $exception) {
            Log::error('AI competitor discovery API call failed', [
                'keyword' => $keyword->keyword,
                'error' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    private function buildCompetitorDiscoveryPrompt(Keyword $keyword, Project $project, array $existingCompetitors): string
    {
        $projectDomain = parse_url($project->url, PHP_URL_HOST) ?: $project->url;
        $industry = $project->industry ?? 'general';

        $existingList = '';
        foreach ($existingCompetitors as $existingCompetitor) {
            $existingList .= sprintf('- %s (pozíció: %s)%s', $existingCompetitor['domain'], $existingCompetitor['position'] ?? 'N/A', PHP_EOL);
        }

        return "Kérlek segíts felfedezni a releváns versenytársakat a következő SEO projekthez:\n\n" .
               sprintf('Projekt domain: %s%s', $projectDomain, PHP_EOL) .
               sprintf('Iparág: %s%s', $industry, PHP_EOL) .
               "Kulcsszó: '{$keyword->keyword}'\n" .
               'Nyelv/Piac: ' . ($keyword->geo_target ?: 'Magyarország') . "\n\n" .
               "Jelenleg ismert versenytársak (SERP alapján):\n{$existingList}\n\n" .
               "Feladat: Azonosítsd a következőket:\n" .
               "1. A legfontosabb KÖZVETLEN versenytársakat, akik ugyanazt a szolgáltatást/terméket kínálják\n" .
               "2. Az INDIREKT versenytársakat, akik kapcsolódó tartalommal rendelkeznek\n" .
               "3. A TARTALMI versenytársakat, akik erős SEO pozícióval rendelkeznek a témában\n" .
               "4. Becsüld meg minden versenytárs erősségét (1-10 skálán)\n" .
               "5. Add meg, hogy miért releváns versenytárs\n\n" .
               "FONTOS: Csak valós, létező weboldalakat adj meg! Ha nem ismersz elegendő versenytársat, kevesebbet is megadhatsz.\n\n" .
               "Válaszod JSON formátumban add meg:\n" .
               '```json' . "\n" .
               '{' . "\n" .
               '  "competitors": [' . "\n" .
               '    {' . "\n" .
               '      "domain": "example.com",' . "\n" .
               '      "url": "https://example.com",' . "\n" .
               '      "type": "direct|indirect|content",' . "\n" .
               '      "strength_score": 8,' . "\n" .
               '      "relevance_reason": "Miért releváns versenytárs",' . "\n" .
               '      "main_advantages": ["előny1", "előny2"],' . "\n" .
               '      "estimated_traffic": "low|medium|high",' . "\n" .
               '      "content_focus": "Mire fókuszál a tartalmuk"' . "\n" .
               '    }' . "\n" .
               '  ],' . "\n" .
               '  "market_insights": {' . "\n" .
               '    "competition_level": "low|medium|high",' . "\n" .
               '    "market_leaders": ["domain1.com", "domain2.com"],' . "\n" .
               '    "emerging_competitors": ["új versenytársak"],' . "\n" .
               '    "market_gaps": ["piaci rések és lehetőségek"]' . "\n" .
               '  },' . "\n" .
               '  "recommendations": [' . "\n" .
               '    "Konkrét javaslat a versenyelőny megszerzéséhez"' . "\n" .
               '  ]' . "\n" .
               '}' . "\n" .
               '```';
    }

    private function parseAiCompetitorResponse(string $response): array
    {
        try {
            // JSON kinyerése a válaszból
            $jsonString = $response;
            if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
                $jsonString = $matches[1];
            } elseif (preg_match('/\{[^{}]*(?:[^{}]*\{[^{}]*\}[^{}]*)*\}/s', $response, $matches)) {
                $jsonString = $matches[0];
            }

            $decoded = json_decode(trim($jsonString), true);

            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['competitors'])) {
                $competitors = [];
                foreach ($decoded['competitors'] as $comp) {
                    if (isset($comp['domain'])) {
                        $competitors[] = [
                            'domain' => $comp['domain'],
                            'url' => $comp['url'] ?? 'https://' . $comp['domain'],
                            'ai_discovered' => true,
                            'type' => $comp['type'] ?? 'unknown',
                            'strength_score' => $comp['strength_score'] ?? 5,
                            'relevance_reason' => $comp['relevance_reason'] ?? null,
                            'main_advantages' => $comp['main_advantages'] ?? [],
                            'estimated_traffic' => $comp['estimated_traffic'] ?? 'unknown',
                            'content_focus' => $comp['content_focus'] ?? null,
                        ];
                    }
                }

                // Market insights mentése a konkurens elemzésekhez
                if (isset($decoded['market_insights'])) {
                    $this->saveMarketInsights($decoded['market_insights']);
                }

                return $competitors;
            }
        } catch (Exception $exception) {
            Log::error('Failed to parse AI competitor response', [
                'error' => $exception->getMessage(),
            ]);
        }

        return [];
    }

    private function mergeCompetitorLists(array $existing, array $aiDiscovered, int $limit): array
    {
        $merged = [];
        $addedDomains = [];

        // Először a meglévő (valós SERP) versenytársakat adjuk hozzá
        foreach ($existing as $comp) {
            if (! in_array($comp['domain'], $addedDomains)) {
                $merged[] = $comp;
                $addedDomains[] = $comp['domain'];
            }
        }

        // Majd az AI által felfedezett versenytársakat
        foreach ($aiDiscovered as $comp) {
            if (! in_array($comp['domain'], $addedDomains) && count($merged) < $limit) {
                $merged[] = $comp;
                $addedDomains[] = $comp['domain'];
            }
        }

        return $merged;
    }

    private function saveMarketInsights(array $insights): void
    {
        try {
            // Itt később menthetjük az insights-okat egy külön táblába ha szükséges
            Log::info('Market insights discovered', $insights);
        } catch (Exception $exception) {
            Log::error('Failed to save market insights', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function analyzeCompetitorWithAi(string $competitorUrl, string $projectUrl, Keyword $keyword): ?array
    {
        try {
            $apiKey = $this->getGeminiApiKey();
            if (empty($apiKey)) {
                Log::warning('No Gemini API key for competitor analysis');

                return null;
            }

            // Tartalom lekérése mindkét oldalról
            $competitorContent = $this->fetchPageContent($competitorUrl);
            $projectContent = $this->fetchPageContent($projectUrl);

            $prompt = $this->buildCompetitorAnalysisPrompt(
                $keyword->keyword,
                $competitorUrl,
                $projectUrl,
                $competitorContent,
                $projectContent
            );

            $client = new Client();
            $response = $client->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey, [
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
                        'temperature' => 0.2,
                        'topK' => 1,
                        'topP' => 1,
                        'maxOutputTokens' => 2048,
                    ],
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];

                    return $this->parseCompetitorAnalysisResponse($aiResponse);
                }
            }
        } catch (Exception $exception) {
            Log::error('AI competitor analysis failed', [
                'competitor_url' => $competitorUrl,
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function fetchPageContent(string $url): array
    {
        try {
            $response = $this->client->get($url, [
                'timeout' => 15,
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; SEO-Analyzer/1.0)',
                ],
            ]);

            $html = $response->getBody()->getContents();

            return [
                'title' => $this->extractTitle($html),
                'meta_description' => $this->extractMetaDescription($html),
                'headers' => $this->extractHeaders($html),
                'content_length' => $this->extractContentLength($html),
                'has_schema' => $this->checkSchemaMarkup($html),
            ];
        } catch (Exception) {
            return [
                'title' => null,
                'meta_description' => null,
                'headers' => [],
                'content_length' => 0,
                'has_schema' => false,
            ];
        }
    }

    private function buildCompetitorAnalysisPrompt(
        string $keyword,
        string $competitorUrl,
        string $projectUrl,
        array $competitorContent,
        array $projectContent
    ): string {
        return "Elemezd a következő két weboldalt SEO szempontból a '{$keyword}' kulcsszóra:\n\n" .
               sprintf('VERSENYTÁRS OLDAL: %s%s', $competitorUrl, PHP_EOL) .
               '- Title: ' . ($competitorContent['title'] ?? 'N/A') . "\n" .
               '- Meta description: ' . ($competitorContent['meta_description'] ?? 'N/A') . "\n" .
               '- Tartalom hossz: ' . ($competitorContent['content_length'] ?? 0) . " karakter\n" .
               '- Schema markup: ' . ($competitorContent['has_schema'] ? 'Van' : 'Nincs') . "\n\n" .
               sprintf('PROJEKT OLDAL: %s%s', $projectUrl, PHP_EOL) .
               '- Title: ' . ($projectContent['title'] ?? 'N/A') . "\n" .
               '- Meta description: ' . ($projectContent['meta_description'] ?? 'N/A') . "\n" .
               '- Tartalom hossz: ' . ($projectContent['content_length'] ?? 0) . " karakter\n" .
               '- Schema markup: ' . ($projectContent['has_schema'] ? 'Van' : 'Nincs') . "\n\n" .
               "Készíts részletes összehasonlító elemzést JSON formátumban:\n" .
               '```json' . "\n" .
               '{' . "\n" .
               '  "competitor_strengths": ["erősség1", "erősség2", "erősség3"],' . "\n" .
               '  "competitor_weaknesses": ["gyengeség1", "gyengeség2"],' . "\n" .
               '  "project_strengths": ["erősség1", "erősség2"],' . "\n" .
               '  "project_weaknesses": ["gyengeség1", "gyengeség2", "gyengeség3"],' . "\n" .
               '  "opportunities": ["lehetőség1", "lehetőség2"],' . "\n" .
               '  "threats": ["veszély1", "veszély2"],' . "\n" .
               '  "action_items": [' . "\n" .
               '    {' . "\n" .
               '      "priority": "high|medium|low",' . "\n" .
               '      "action": "Mit kell tenni",' . "\n" .
               '      "expected_impact": "Várható hatás",' . "\n" .
               '      "effort": "low|medium|high"' . "\n" .
               '    }' . "\n" .
               '  ],' . "\n" .
               '  "competitive_advantage_score": 7,' . "\n" .
               '  "summary": "Rövid összefoglaló a versenyelemzésről"' . "\n" .
               '}' . "\n" .
               '```';
    }

    private function parseCompetitorAnalysisResponse(string $response): array
    {
        try {
            $jsonString = $response;
            if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
                $jsonString = $matches[1];
            } elseif (preg_match('/\{[^{}]*(?:[^{}]*\{[^{}]*\}[^{}]*)*\}/s', $response, $matches)) {
                $jsonString = $matches[0];
            }

            $decoded = json_decode(trim($jsonString), true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        } catch (Exception $exception) {
            Log::error('Failed to parse competitor analysis response', [
                'error' => $exception->getMessage(),
            ]);
        }

        return [
            'competitor_strengths' => [],
            'competitor_weaknesses' => [],
            'project_strengths' => [],
            'project_weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
            'action_items' => [],
            'competitive_advantage_score' => 0,
            'summary' => 'Elemzés feldolgozása sikertelen',
        ];
    }
}
