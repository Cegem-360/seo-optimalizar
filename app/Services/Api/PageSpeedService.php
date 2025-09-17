<?php

namespace App\Services\Api;

use App\Models\Keyword;
use App\Models\PageSpeedAnalysis;
use App\Models\Project;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Log;

class PageSpeedService
{
    private readonly string $apiKey;

    private readonly Client $client;

    private string $baseUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    public function __construct(?string $apiKey = null, ?Repository $repository = null)
    {
        if ($apiKey !== null) {
            $this->apiKey = $apiKey;
        } elseif ($repository instanceof Repository) {
            $this->apiKey = $repository->get('services.google.pagespeed_api_key', '');
        } else {
            $this->apiKey = config('services.google.pagespeed_api_key', '');
        }

        $this->client = new Client();
    }

    public function analyzeUrl(string $url, string $strategy = 'desktop', ?Project $project = null, ?Keyword $keyword = null): ?PageSpeedAnalysis
    {
        try {
            if (empty($this->apiKey)) {
                Log::warning('PageSpeed API key is missing');

                return null;
            }

            $response = $this->client->get($this->baseUrl, [
                'query' => [
                    'url' => $url,
                    'key' => $this->apiKey,
                    'strategy' => $strategy,
                    'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = json_decode($response->getBody()->getContents(), true);

            return $this->savePageSpeedAnalysis($data, $url, $strategy, $project, $keyword);
        } catch (Exception $exception) {
            Log::error('PageSpeed analysis failed', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function savePageSpeedAnalysis(array $data, string $url, string $strategy, ?Project $project, ?Keyword $keyword): PageSpeedAnalysis
    {
        $lighthouseResult = $data['lighthouseResult'] ?? [];
        $audits = $lighthouseResult['audits'] ?? [];
        $categories = $lighthouseResult['categories'] ?? [];

        // Core Web Vitals
        $metrics = $this->extractCoreWebVitals($audits);

        // Resource breakdown
        $resourceBreakdown = $this->extractResourceBreakdown($audits);

        // Opportunities and diagnostics
        $opportunities = $this->extractOpportunities($audits);
        $diagnostics = $this->extractDiagnostics($audits);

        // Images analysis
        $imageAnalysis = $this->extractImageAnalysis($audits);

        return PageSpeedAnalysis::query()->create([
            'project_id' => $project?->id,
            'keyword_id' => $keyword?->id,
            'tested_url' => $url,
            'device_type' => $strategy,

            // Core Web Vitals
            'lcp' => $metrics['lcp'],
            'fid' => $metrics['fid'],
            'cls' => $metrics['cls'],
            'fcp' => $metrics['fcp'],
            'inp' => $metrics['inp'],
            'ttfb' => $metrics['ttfb'],

            // Scores
            'performance_score' => isset($categories['performance']) ? round($categories['performance']['score'] * 100) : null,
            'accessibility_score' => isset($categories['accessibility']) ? round($categories['accessibility']['score'] * 100) : null,
            'best_practices_score' => isset($categories['best-practices']) ? round($categories['best-practices']['score'] * 100) : null,
            'seo_score' => isset($categories['seo']) ? round($categories['seo']['score'] * 100) : null,

            // Page metrics
            'total_page_size' => $audits['total-byte-weight']['numericValue'] ?? null,
            'total_requests' => $audits['network-requests']['details']['items'] ?? null ? count($audits['network-requests']['details']['items']) : null,
            'load_time' => isset($audits['interactive']) ? $audits['interactive']['numericValue'] / 1000 : null,

            // Resource details
            'resource_breakdown' => $resourceBreakdown,
            'third_party_resources' => $this->extractThirdPartyResources($audits),

            // Improvements
            'opportunities' => $opportunities,
            'diagnostics' => $diagnostics,

            // Images
            'images_count' => $imageAnalysis['total_images'],
            'unoptimized_images' => $imageAnalysis['unoptimized_images'],
            'images_without_alt' => $imageAnalysis['images_without_alt'],

            // Render blocking
            'render_blocking_resources' => isset($audits['render-blocking-resources']['details']['items']) ? count($audits['render-blocking-resources']['details']['items']) : 0,
            'unused_css_bytes' => $audits['unused-css-rules']['wastedBytes'] ?? 0,
            'unused_js_bytes' => $audits['unused-javascript']['wastedBytes'] ?? 0,

            'analysis_source' => 'pagespeed',
            'analyzed_at' => now(),
            'raw_response' => $data,
        ]);
    }

    private function extractCoreWebVitals(array $audits): array
    {
        return [
            'lcp' => isset($audits['largest-contentful-paint']) ? $audits['largest-contentful-paint']['numericValue'] / 1000 : null,
            'fid' => isset($audits['max-potential-fid']) ? $audits['max-potential-fid']['numericValue'] : null,
            'cls' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
            'fcp' => isset($audits['first-contentful-paint']) ? $audits['first-contentful-paint']['numericValue'] / 1000 : null,
            'inp' => isset($audits['interaction-to-next-paint']) ? $audits['interaction-to-next-paint']['numericValue'] : null,
            'ttfb' => isset($audits['server-response-time']) ? $audits['server-response-time']['numericValue'] : null,
        ];
    }

    private function extractResourceBreakdown(array $audits): array
    {
        if (! isset($audits['resource-summary']['details']['items'])) {
            return [];
        }

        $breakdown = [];
        foreach ($audits['resource-summary']['details']['items'] as $item) {
            $breakdown[$item['resourceType']] = [
                'size' => $item['transferSize'] ?? 0,
                'count' => $item['requestCount'] ?? 0,
            ];
        }

        return $breakdown;
    }

    private function extractOpportunities(array $audits): array
    {
        $opportunities = [];

        $opportunityAudits = [
            'render-blocking-resources',
            'unused-css-rules',
            'unused-javascript',
            'modern-image-formats',
            'uses-optimized-images',
            'uses-text-compression',
            'uses-responsive-images',
            'efficient-animated-content',
        ];

        foreach ($opportunityAudits as $opportunityAudit) {
            if (isset($audits[$opportunityAudit]) && $audits[$opportunityAudit]['score'] < 0.9) {
                $opportunities[] = [
                    'id' => $opportunityAudit,
                    'title' => $audits[$opportunityAudit]['title'] ?? '',
                    'description' => $audits[$opportunityAudit]['description'] ?? '',
                    'savings_ms' => $audits[$opportunityAudit]['numericValue'] ?? 0,
                    'savings_bytes' => $audits[$opportunityAudit]['wastedBytes'] ?? 0,
                ];
            }
        }

        return $opportunities;
    }

    private function extractDiagnostics(array $audits): array
    {
        $diagnostics = [];

        $diagnosticAudits = [
            'font-display',
            'largest-contentful-paint-element',
            'layout-shift-elements',
            'long-tasks',
            'non-composited-animations',
            'unsized-images',
        ];

        foreach ($diagnosticAudits as $diagnosticAudit) {
            if (isset($audits[$diagnosticAudit]) && $audits[$diagnosticAudit]['score'] < 1) {
                $diagnostics[] = [
                    'id' => $diagnosticAudit,
                    'title' => $audits[$diagnosticAudit]['title'] ?? '',
                    'description' => $audits[$diagnosticAudit]['description'] ?? '',
                    'displayValue' => $audits[$diagnosticAudit]['displayValue'] ?? '',
                ];
            }
        }

        return $diagnostics;
    }

    private function extractImageAnalysis(array $audits): array
    {
        $totalImages = 0;
        $unoptimizedImages = 0;
        $imagesWithoutAlt = 0;

        if (isset($audits['uses-optimized-images']['details']['items'])) {
            $unoptimizedImages = count($audits['uses-optimized-images']['details']['items']);
        }

        if (isset($audits['image-alt']['details']['items'])) {
            $imagesWithoutAlt = count($audits['image-alt']['details']['items']);
        }

        if (isset($audits['resource-summary']['details']['items'])) {
            foreach ($audits['resource-summary']['details']['items'] as $item) {
                if ($item['resourceType'] === 'image') {
                    $totalImages = $item['requestCount'] ?? 0;
                    break;
                }
            }
        }

        return [
            'total_images' => $totalImages,
            'unoptimized_images' => $unoptimizedImages,
            'images_without_alt' => $imagesWithoutAlt,
        ];
    }

    private function extractThirdPartyResources(array $audits): array
    {
        if (! isset($audits['third-party-summary']['details']['items'])) {
            return [];
        }

        $thirdParty = [];
        foreach ($audits['third-party-summary']['details']['items'] as $item) {
            $thirdParty[] = [
                'entity' => $item['entity'] ?? 'Unknown',
                'transferSize' => $item['transferSize'] ?? 0,
                'blockingTime' => $item['blockingTime'] ?? 0,
            ];
        }

        return $thirdParty;
    }
}
