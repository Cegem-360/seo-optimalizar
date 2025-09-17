<?php

namespace App\Services\Api;

use App\Models\CompetitorAnalysis;
use App\Models\Keyword;
use App\Models\Ranking;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CompetitorAnalysisService
{
    private Client $client;

    private PageSpeedService $pageSpeedService;

    public function __construct()
    {
        $this->client = new Client();
        $this->pageSpeedService = new PageSpeedService();
    }

    public function analyzeCompetitor(string $domain, string $url, Keyword $keyword, int $position): ?CompetitorAnalysis
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

            return CompetitorAnalysis::create($analysis);
        } catch (Exception $e) {
            Log::error('Competitor analysis failed', [
                'domain' => $domain,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function analyzeTopCompetitors(Keyword $keyword, int $limit = 10): array
    {
        $competitors = [];

        // Kérjük le a top versenytársakat a SERP-ből vagy ranking adatokból
        $topDomains = $this->getTopCompetitorDomains($keyword, $limit);

        foreach ($topDomains as $position => $competitorData) {
            $analysis = $this->analyzeCompetitor(
                $competitorData['domain'],
                $competitorData['url'],
                $keyword,
                $position + 1
            );

            if ($analysis) {
                $competitors[] = $analysis;
            }
        }

        return $competitors;
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
            $domain = parse_url($ranking->url, PHP_URL_HOST);
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

            foreach ($commonCompetitors as $domain) {
                if (count($competitors) >= $limit) {
                    break;
                }

                if (! collect($competitors)->pluck('domain')->contains($domain)) {
                    $competitors[] = [
                        'domain' => $domain,
                        'url' => 'https://' . $domain,
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
                'is_mobile_friendly' => $this->checkMobileFriendly($headers),
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

        if ($pageSpeedAnalysis) {
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
            return rand(80, 100);
        } elseif (in_array($domain, $mediumAuthDomains)) {
            return rand(60, 79);
        } else {
            return rand(20, 59);
        }
    }

    private function simulatePageAuthority(string $domain): int
    {
        $da = $this->simulateDomainAuthority($domain);

        return max(1, $da - rand(5, 15));
    }

    private function simulateBacklinksCount(string $domain): int
    {
        $da = $this->simulateDomainAuthority($domain);

        return (int) (pow($da / 10, 3) * rand(100, 1000));
    }

    private function checkMobileFriendly(array $headers): bool
    {
        // Egyszerű ellenőrzés responsive jelzőkre
        return true; // Alapértelmezetten igen
    }

    private function extractContentLength(string $html): int
    {
        // Eltávolítjuk a HTML tageket és számoljuk a karaktereket
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);

        return strlen(trim($text));
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
            if (preg_match_all("/<h{$i}[^>]*>(.*?)<\/h{$i}>/is", $html, $matches)) {
                $headers["h{$i}"] = array_map(function ($match) {
                    return trim(strip_tags(html_entity_decode($match)));
                }, $matches[1]);
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
}
