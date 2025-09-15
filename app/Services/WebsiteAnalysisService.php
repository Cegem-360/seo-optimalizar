<?php

namespace App\Services;

use App\Models\AnalysisSection;
use App\Models\WebsiteAnalysis;
use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;

class WebsiteAnalysisService
{
    public function __construct(private readonly DatabaseManager $databaseManager) {}

    public function createAnalysis(array $data): WebsiteAnalysis
    {
        return $this->databaseManager->transaction(fn () => WebsiteAnalysis::query()->create([
            'project_id' => $data['project_id'],
            'url' => $data['url'],
            'analysis_type' => $data['analysis_type'],
            'ai_provider' => $data['ai_provider'],
            'ai_model' => $data['ai_model'] ?? null,
            'request_params' => $data['request_params'] ?? null,
            'status' => 'pending',
        ]));
    }

    public function processAiResponse(WebsiteAnalysis $websiteAnalysis, string $aiResponse): WebsiteAnalysis
    {
        try {
            $this->databaseManager->beginTransaction();

            // Mentjük a nyers választ
            $websiteAnalysis->update([
                'raw_response' => $aiResponse,
                'status' => 'processing',
            ]);

            // Próbáljuk JSON-ként értelmezni
            $parsedData = $this->parseAiResponse($aiResponse);

            if ($parsedData !== null && $parsedData !== []) {
                // Strukturált adatok mentése
                $this->saveStructuredData($websiteAnalysis, $parsedData);

                $websiteAnalysis->update([
                    'status' => 'completed',
                    'analyzed_at' => now(),
                ]);
            } else {
                // Ha nem sikerül parse-olni, csak szövegként mentjük
                $websiteAnalysis->update([
                    'status' => 'completed',
                    'analyzed_at' => now(),
                ]);
            }

            $this->databaseManager->commit();

            return $websiteAnalysis->fresh(['sections']);
        } catch (Exception $exception) {
            $this->databaseManager->rollBack();

            $websiteAnalysis->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Log::error('Failed to process AI response', [
                'analysis_id' => $websiteAnalysis->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    protected function parseAiResponse(string $response): ?array
    {
        // Próbáljuk JSON-ként értelmezni
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Próbáljuk kivenni a JSON részt a szövegből
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Ha markdown code block-ban van
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    protected function saveStructuredData(WebsiteAnalysis $websiteAnalysis, array $data): void
    {
        // Összpontszám mentése
        if (isset($data['overall_score'])) {
            $websiteAnalysis->update(['overall_score' => $data['overall_score']]);
        }

        // Különböző pontszámok mentése
        if (isset($data['scores'])) {
            $websiteAnalysis->update(['scores' => $data['scores']]);
        }

        // Metaadatok mentése
        if (isset($data['metadata'])) {
            $websiteAnalysis->update(['metadata' => $data['metadata']]);
        }

        // Szakaszok mentése
        if (isset($data['sections']) && is_array($data['sections'])) {
            foreach ($data['sections'] as $index => $section) {
                $this->createSection($websiteAnalysis, $section, $index);
            }
        }

        // Ha nincs sections, de van más struktúra
        if (! isset($data['sections'])) {
            $this->createSectionsFromData($websiteAnalysis, $data);
        }
    }

    protected function createSection(WebsiteAnalysis $websiteAnalysis, array $sectionData, int $priority = 0): AnalysisSection
    {
        return AnalysisSection::query()->create([
            'website_analysis_id' => $websiteAnalysis->id,
            'section_type' => $sectionData['type'] ?? 'general',
            'section_name' => $sectionData['name'] ?? $sectionData['title'] ?? 'Section ' . ($priority + 1),
            'score' => $sectionData['score'] ?? null,
            'status' => $sectionData['status'] ?? $this->determineStatus($sectionData['score'] ?? null),
            'findings' => $sectionData['findings'] ?? $sectionData['issues'] ?? [],
            'recommendations' => $sectionData['recommendations'] ?? $sectionData['suggestions'] ?? [],
            'data' => $sectionData['data'] ?? $sectionData,
            'summary' => $sectionData['summary'] ?? $sectionData['description'] ?? null,
            'priority' => $sectionData['priority'] ?? $priority,
        ]);
    }

    protected function createSectionsFromData(WebsiteAnalysis $websiteAnalysis, array $data): void
    {
        $priority = 0;

        // Főbb témakörök automatikus kinyerése
        $knownSections = [
            'seo' => 'SEO elemzés',
            'performance' => 'Teljesítmény',
            'accessibility' => 'Hozzáférhetőség',
            'usability' => 'Használhatóság',
            'content' => 'Tartalom',
            'technical' => 'Technikai',
            'meta' => 'Meta adatok',
            'images' => 'Képek',
            'links' => 'Linkek',
            'mobile' => 'Mobil',
        ];

        foreach ($data as $key => $value) {
            if (is_array($value) && ! in_array($key, ['overall_score', 'scores', 'metadata'])) {
                $sectionName = $knownSections[$key] ?? ucfirst(str_replace('_', ' ', $key));

                $this->createSection($websiteAnalysis, [
                    'type' => $key,
                    'name' => $sectionName,
                    'data' => $value,
                    'priority' => $priority++,
                ]);
            }
        }
    }

    protected function determineStatus(?int $score): string
    {
        if ($score === null) {
            return 'neutral';
        }
        if ($score >= 80) {
            return 'good';
        }

        if ($score >= 50) {
            return 'warning';
        } else {
            return 'error';
        }
    }

    public function getAnalysisPrompt(string $analysisType, string $url): string
    {
        $prompts = [
            'seo' => sprintf('Elemezd a következő weboldalt SEO szempontból: %s. Adj strukturált választ JSON formátumban, amely tartalmazza: overall_score (0-100), sections tömböt különböző SEO aspektusokkal (title, meta, headings, content, images, links), mindegyik szakaszhoz score, findings, recommendations mezőkkel.', $url),

            'ux' => sprintf('Elemezd a következő weboldalt UX szempontból: %s. Adj strukturált választ JSON formátumban, amely tartalmazza: overall_score (0-100), sections tömböt (navigation, layout, readability, forms, cta), mindegyik szakaszhoz score, findings, recommendations mezőkkel.', $url),

            'content' => sprintf('Elemezd a következő weboldal tartalmát: %s. Adj strukturált választ JSON formátumban, amely tartalmazza: overall_score (0-100), sections tömböt (quality, structure, keywords, readability), mindegyik szakaszhoz score, findings, recommendations mezőkkel.', $url),

            'technical' => sprintf('Végezz technikai elemzést a következő weboldalon: %s. Adj strukturált választ JSON formátumban, amely tartalmazza: overall_score (0-100), sections tömböt (performance, security, mobile, accessibility), mindegyik szakaszhoz score, findings, recommendations mezőkkel.', $url),

            'competitor' => sprintf('Végezz versenytárs elemzést a következő weboldalon: %s. Adj strukturált választ JSON formátumban, amely tartalmazza: overall_score (0-100), sections tömböt (strengths, weaknesses, opportunities, threats), mindegyik szakaszhoz findings, recommendations mezőkkel.', $url),
        ];

        return $prompts[$analysisType] ?? $prompts['seo'];
    }

    public function getDemoResponse(string $analysisType): string
    {
        $demoResponses = [
            'seo' => json_encode([
                'overall_score' => 78,
                'scores' => [
                    'title' => 85,
                    'meta_description' => 72,
                    'headings' => 80,
                    'content' => 75,
                    'images' => 70,
                    'links' => 82,
                ],
                'sections' => [
                    [
                        'type' => 'title',
                        'name' => 'Címek optimalizálása',
                        'score' => 85,
                        'status' => 'good',
                        'findings' => [
                            'A főcím tartalmazza a kulcsszót',
                            'A címhierarchia megfelelő',
                        ],
                        'recommendations' => [
                            'Adj hozzá még egy H2 címet',
                            'Optimalizáld a meta title hosszát',
                        ],
                    ],
                    [
                        'type' => 'content',
                        'name' => 'Tartalom elemzés',
                        'score' => 75,
                        'status' => 'warning',
                        'findings' => [
                            'A tartalom hossza megfelelő',
                            'Hiányoznak a belső linkek',
                        ],
                        'recommendations' => [
                            'Adj hozzá több belső linket',
                            'Használj több variációt a kulcsszóból',
                        ],
                    ],
                ],
            ]),

            'ux' => json_encode([
                'overall_score' => 82,
                'scores' => [
                    'navigation' => 85,
                    'layout' => 80,
                    'readability' => 78,
                    'forms' => 85,
                    'cta' => 82,
                ],
                'sections' => [
                    [
                        'type' => 'navigation',
                        'name' => 'Navigáció',
                        'score' => 85,
                        'status' => 'good',
                        'findings' => [
                            'Egyértelmű menüstruktúra',
                            'Responsive navigáció',
                        ],
                        'recommendations' => [
                            'Adj hozzá breadcrumb navigációt',
                            'Javítsd a mobil menü elérhetőségét',
                        ],
                    ],
                ],
            ]),

            'technical' => json_encode([
                'overall_score' => 71,
                'scores' => [
                    'performance' => 68,
                    'security' => 85,
                    'mobile' => 72,
                    'accessibility' => 65,
                ],
                'sections' => [
                    [
                        'type' => 'performance',
                        'name' => 'Teljesítmény',
                        'score' => 68,
                        'status' => 'warning',
                        'findings' => [
                            'Lassú betöltési idő',
                            'Nagy képfájlok',
                        ],
                        'recommendations' => [
                            'Optimalizáld a képeket',
                            'Használj CDN-t',
                            'Minimalizáld a CSS és JS fájlokat',
                        ],
                    ],
                ],
            ]),
        ];

        return $demoResponses[$analysisType] ?? $demoResponses['seo'];
    }
}
