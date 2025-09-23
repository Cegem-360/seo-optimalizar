<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnalysisSection;
use App\Models\ApiCredential;
use App\Models\Project;
use App\Models\WebsiteAnalysis;
use App\Services\Api\GeminiApiService;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebsiteAnalysisService
{
    public function createAnalysis(array $data): WebsiteAnalysis
    {
        return WebsiteAnalysis::query()->create([
            'project_id' => $data['project_id'],
            'url' => $data['url'],
            'analysis_type' => $data['analysis_type'],
            'ai_provider' => $data['ai_provider'],
            'ai_model' => $data['ai_model'] ?? null,
            'request_params' => $data['request_params'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function processAiResponse(WebsiteAnalysis $websiteAnalysis, string $aiResponse): WebsiteAnalysis
    {
        try {
            DB::beginTransaction();

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

            DB::commit();

            return $websiteAnalysis->fresh(['sections']);
        } catch (Exception $exception) {
            DB::rollBack();

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
            $priority = 0;
            foreach ($data['sections'] as $index => $section) {
                // Csak akkor dolgozzuk fel, ha a section tömb
                if (! is_array($section)) {
                    continue;
                }

                // Ha a section egy asszociatív tömb a szekció nevekkel kulcsként
                if (! isset($section['type']) && ! isset($section['name'])) {
                    foreach ($section as $sectionKey => $sectionContent) {
                        if (is_array($sectionContent)) {
                            $this->createSection($websiteAnalysis, [
                                'type' => $sectionKey,
                                'name' => $this->getSectionNameFromKey($sectionKey),
                                'score' => $sectionContent['score'] ?? null,
                                'findings' => $sectionContent['findings'] ?? [],
                                'recommendations' => $sectionContent['recommendations'] ?? [],
                                'data' => $sectionContent,
                            ], $priority++);
                        }
                    }
                } else {
                    // Hagyományos formátum támogatása
                    $this->createSection($websiteAnalysis, $section, $index);
                }
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
                $sectionName = $knownSections[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));

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
        }

        return 'error';
    }

    protected function getSectionNameFromKey(string $key): string
    {
        $sectionNames = [
            'quality' => 'Minőség',
            'structure' => 'Struktúra',
            'keywords' => 'Kulcsszavak',
            'readability' => 'Olvashatóság',
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
            'navigation' => 'Navigáció',
            'layout' => 'Elrendezés',
            'forms' => 'Űrlapok',
            'cta' => 'Cselekvésre ösztönzők',
            'security' => 'Biztonság',
            'title' => 'Címek',
            'meta_description' => 'Meta leírás',
            'headings' => 'Fejlécek',
        ];

        return $sectionNames[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Run AI analysis using the specified provider
     */
    public function runAiAnalysis(WebsiteAnalysis $websiteAnalysis): void
    {
        try {
            $project = $websiteAnalysis->project;

            if (! $project instanceof Project) {
                throw new Exception('Projekt nem található');
            }

            if ($websiteAnalysis->ai_provider === 'gemini') {
                // Get Gemini service for the project
                $geminiApiService = new GeminiApiService($project);

                // Get the appropriate prompt
                $prompt = $this->getAnalysisPrompt($websiteAnalysis->analysis_type, $websiteAnalysis->url);

                // Call Gemini API
                $response = $geminiApiService->analyzeWebsite(
                    $websiteAnalysis->url,
                    $websiteAnalysis->analysis_type,
                    $prompt,
                );

                if ($response !== null && $response !== '' && $response !== '0') {
                    // Process the AI response
                    $this->processAiResponse($websiteAnalysis, $response);
                } else {
                    throw new Exception('Nem sikerült választ kapni a Gemini API-tól');
                }
            } else {
                throw new Exception('Nem támogatott AI szolgáltató: ' . $websiteAnalysis->ai_provider);
            }
        } catch (Exception $exception) {
            $websiteAnalysis->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function getAnalysisPrompt(string $analysisType, string $url): string
    {
        $basePrompt = 'FONTOS: A válaszod CSAK tiszta JSON legyen, semmi más szöveg vagy magyarázat! ';

        $prompts = [
            'seo' => $basePrompt . sprintf('Elemezd a következő weboldalt SEO szempontból: %s. A válasz pontosan ebben a JSON formátumban legyen: {"overall_score": szám 0-100 között, "sections": [{"title": {"score": szám, "findings": ["megállapítás1", "megállapítás2"], "recommendations": ["javaslat1", "javaslat2"]}}, {"meta": {"score": szám, "findings": [...], "recommendations": [...]}}, {"headings": {...}}, {"content": {...}}, {"images": {...}}, {"links": {...}}]}', $url),

            'ux' => $basePrompt . sprintf('Elemezd a következő weboldalt UX szempontból: %s. A válasz pontosan ebben a JSON formátumban legyen: {"overall_score": szám 0-100 között, "sections": [{"navigation": {"score": szám, "findings": ["megállapítás1", "megállapítás2"], "recommendations": ["javaslat1", "javaslat2"]}}, {"layout": {"score": szám, "findings": [...], "recommendations": [...]}}, {"readability": {...}}, {"forms": {...}}, {"cta": {...}}]}', $url),

            'content' => $basePrompt . sprintf('Elemezd a következő weboldal tartalmát: %s. A válasz pontosan ebben a JSON formátumban legyen: {"overall_score": szám 0-100 között, "sections": [{"quality": {"score": szám, "findings": ["megállapítás1", "megállapítás2"], "recommendations": ["javaslat1", "javaslat2"]}}, {"structure": {"score": szám, "findings": [...], "recommendations": [...]}}, {"keywords": {...}}, {"readability": {...}}]}', $url),

            'technical' => $basePrompt . sprintf('Végezz technikai elemzést a következő weboldalon: %s. A válasz pontosan ebben a JSON formátumban legyen: {"overall_score": szám 0-100 között, "sections": [{"performance": {"score": szám, "findings": ["megállapítás1", "megállapítás2"], "recommendations": ["javaslat1", "javaslat2"]}}, {"security": {"score": szám, "findings": [...], "recommendations": [...]}}, {"mobile": {...}}, {"accessibility": {...}}]}', $url),

            'competitor' => $basePrompt . sprintf('Végezz versenytárs elemzést a következő weboldalon: %s. A válasz pontosan ebben a JSON formátumban legyen: {"overall_score": szám 0-100 között, "sections": [{"strengths": {"score": szám, "findings": ["erősség1", "erősség2"], "recommendations": ["javaslat1", "javaslat2"]}}, {"weaknesses": {"score": szám, "findings": [...], "recommendations": [...]}}, {"opportunities": {...}}, {"threats": {...}}]}', $url),
        ];

        return $prompts[$analysisType] ?? $prompts['seo'];
    }

    public static function getAvailableAiProviders(): array
    {
        $project = Filament::getTenant();

        if (! $project instanceof Project) {
            return [];
        }

        $providers = [];

        // Dinamikusan betöltjük az aktív API credentialeket
        $activeCredentials = ApiCredential::query()->where('project_id', $project->id)
            ->where('is_active', true)
            ->get();

        foreach ($activeCredentials as $activeCredential) {
            $providerName = match ($activeCredential->service) {
                'gemini' => 'Google Gemini',
                'openai' => 'OpenAI (GPT)',
                'claude' => 'Anthropic Claude',
                'ollama' => 'Ollama (Local)',
                default => null,
            };

            if ($providerName !== null) {
                $providers[$activeCredential->service] = $providerName;
            }
        }

        return $providers;
    }

    public static function getModelForProvider(string $provider): string
    {
        return match ($provider) {
            'openai' => 'gpt-4',
            'claude' => 'claude-3-opus',
            'gemini' => 'gemini-2.0-flash',
            'ollama' => 'llama2',
            default => '',
        };
    }
}
