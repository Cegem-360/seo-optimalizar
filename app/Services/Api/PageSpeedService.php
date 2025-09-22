<?php

namespace App\Services\Api;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository;

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
            try {
                $this->apiKey = config('services.google.pagespeed_api_key', '');
            } catch (Exception) {
                $this->apiKey = '';
            }
        }

        $this->client = new Client();
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
