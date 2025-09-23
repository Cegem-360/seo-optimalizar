<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\PageSpeedResult;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

class PageSpeedInsightsService extends BaseApiService
{
    protected string $serviceName = 'google_pagespeed_insights';

    private string $baseUrl = 'https://www.googleapis.com/pagespeedonline/v5';

    public function isConfigured(): bool
    {
        return ! empty(config('services.google.pagespeed_api_key'));
    }

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        $apiKey = config('services.google.pagespeed_api_key');

        if (! $apiKey) {
            throw new Exception('Missing PageSpeed Insights API key in config');
        }

        $pendingRequest->withHeaders([
            'Accept' => 'application/json',
        ]);
    }

    public function testConnection(): bool
    {
        try {
            // Use direct HTTP call instead of makeRequest to avoid facade issues
            $apiKey = config('services.google.pagespeed_api_key');

            if (! $apiKey) {
                return false;
            }

            $client = new Client();
            $response = $client->get($this->baseUrl . '/runPagespeed', [
                'query' => [
                    'key' => $apiKey,
                    'url' => 'https://www.google.com',
                    'strategy' => 'mobile',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $response->getStatusCode() === 200 && isset($data['id']);
        } catch (Exception) {
            return false;
        }
    }

    public function analyzeUrl(string $url, string $strategy = 'mobile', array $categories = ['performance', 'accessibility', 'best-practices', 'seo']): array
    {
        $params = [
            'key' => config('services.google.pagespeed_api_key'),
            'url' => $url,
            'strategy' => $strategy, // 'mobile' or 'desktop'
            'category' => $categories,
        ];

        $response = $this->makeRequest()->get($this->baseUrl . '/runPagespeed', $params);
        $data = $this->handleResponse($response);

        return $this->processPageSpeedData($data);
    }

    public function analyzeProjectUrl(string $strategy = 'mobile'): array
    {
        // Use direct HTTP call to avoid facade issues
        $apiKey = config('services.google.pagespeed_api_key');

        $client = new Client();
        $response = $client->get($this->baseUrl . '/runPagespeed', [
            'query' => [
                'key' => $apiKey,
                'url' => $this->project->url,
                'strategy' => $strategy,
                'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('PageSpeed API request failed: ' . $response->getStatusCode());
        }

        $data = json_decode($response->getBody()->getContents(), true);

        // Simple processing without facades
        $processed = [
            'url' => $data['id'] ?? '',
            'strategy' => $strategy,
            'timestamp' => date('c'),
        ];

        // Overall scores
        if (isset($data['lighthouseResult']['categories'])) {
            $categories = $data['lighthouseResult']['categories'];

            $processed['scores'] = [
                'performance' => isset($categories['performance']) ? round($categories['performance']['score'] * 100) : null,
                'accessibility' => isset($categories['accessibility']) ? round($categories['accessibility']['score'] * 100) : null,
                'best_practices' => isset($categories['best-practices']) ? round($categories['best-practices']['score'] * 100) : null,
                'seo' => isset($categories['seo']) ? round($categories['seo']['score'] * 100) : null,
            ];
        }

        // Core Web Vitals metrics
        if (isset($data['lighthouseResult']['audits'])) {
            $audits = $data['lighthouseResult']['audits'];
            $coreWebVitals = [];

            // First Contentful Paint (FCP)
            if (isset($audits['first-contentful-paint'])) {
                $coreWebVitals['fcp'] = [
                    'value' => $audits['first-contentful-paint']['numericValue'] ?? null,
                    'display_value' => $audits['first-contentful-paint']['displayValue'] ?? null,
                    'score' => $audits['first-contentful-paint']['score'] ?? null,
                ];
            }

            // Largest Contentful Paint (LCP)
            if (isset($audits['largest-contentful-paint'])) {
                $coreWebVitals['lcp'] = [
                    'value' => $audits['largest-contentful-paint']['numericValue'] ?? null,
                    'display_value' => $audits['largest-contentful-paint']['displayValue'] ?? null,
                    'score' => $audits['largest-contentful-paint']['score'] ?? null,
                ];
            }

            // Cumulative Layout Shift (CLS)
            if (isset($audits['cumulative-layout-shift'])) {
                $coreWebVitals['cls'] = [
                    'value' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
                    'display_value' => $audits['cumulative-layout-shift']['displayValue'] ?? null,
                    'score' => $audits['cumulative-layout-shift']['score'] ?? null,
                ];
            }

            // Speed Index
            if (isset($audits['speed-index'])) {
                $coreWebVitals['speed_index'] = [
                    'value' => $audits['speed-index']['numericValue'] ?? null,
                    'display_value' => $audits['speed-index']['displayValue'] ?? null,
                    'score' => $audits['speed-index']['score'] ?? null,
                ];
            }

            $processed['core_web_vitals'] = $coreWebVitals;
        }

        // Store results in database
        $this->storeResults($processed, $data);

        return $processed;
    }

    public function analyzeBothStrategies(?string $url = null): array
    {
        $url = $url !== null && $url !== '' && $url !== '0' ? $url : $this->project->url;

        $results = [
            'mobile' => $this->analyzeUrl($url, 'mobile'),
            'desktop' => $this->analyzeUrl($url, 'desktop'),
        ];

        // Add a small delay between requests to respect rate limits
        sleep(1);

        return $results;
    }

    public function getCoreWebVitals(?string $url = null, string $strategy = 'mobile'): array
    {
        $url = $url !== null && $url !== '' && $url !== '0' ? $url : $this->project->url;
        $analysis = $this->analyzeUrl($url, $strategy);

        $coreWebVitals = [];

        if (isset($analysis['lighthouse_result']['audits'])) {
            $audits = $analysis['lighthouse_result']['audits'];

            // First Contentful Paint (FCP)
            if (isset($audits['first-contentful-paint'])) {
                $coreWebVitals['fcp'] = [
                    'value' => $audits['first-contentful-paint']['numericValue'] ?? null,
                    'display_value' => $audits['first-contentful-paint']['displayValue'] ?? null,
                    'score' => $audits['first-contentful-paint']['score'] ?? null,
                ];
            }

            // Largest Contentful Paint (LCP)
            if (isset($audits['largest-contentful-paint'])) {
                $coreWebVitals['lcp'] = [
                    'value' => $audits['largest-contentful-paint']['numericValue'] ?? null,
                    'display_value' => $audits['largest-contentful-paint']['displayValue'] ?? null,
                    'score' => $audits['largest-contentful-paint']['score'] ?? null,
                ];
            }

            // Cumulative Layout Shift (CLS)
            if (isset($audits['cumulative-layout-shift'])) {
                $coreWebVitals['cls'] = [
                    'value' => $audits['cumulative-layout-shift']['numericValue'] ?? null,
                    'display_value' => $audits['cumulative-layout-shift']['displayValue'] ?? null,
                    'score' => $audits['cumulative-layout-shift']['score'] ?? null,
                ];
            }

            // First Input Delay (FID) - Note: May not always be available
            if (isset($audits['max-potential-fid'])) {
                $coreWebVitals['fid'] = [
                    'value' => $audits['max-potential-fid']['numericValue'] ?? null,
                    'display_value' => $audits['max-potential-fid']['displayValue'] ?? null,
                    'score' => $audits['max-potential-fid']['score'] ?? null,
                ];
            }

            // Total Blocking Time (TBT)
            if (isset($audits['total-blocking-time'])) {
                $coreWebVitals['tbt'] = [
                    'value' => $audits['total-blocking-time']['numericValue'] ?? null,
                    'display_value' => $audits['total-blocking-time']['displayValue'] ?? null,
                    'score' => $audits['total-blocking-time']['score'] ?? null,
                ];
            }

            // Speed Index
            if (isset($audits['speed-index'])) {
                $coreWebVitals['speed_index'] = [
                    'value' => $audits['speed-index']['numericValue'] ?? null,
                    'display_value' => $audits['speed-index']['displayValue'] ?? null,
                    'score' => $audits['speed-index']['score'] ?? null,
                ];
            }
        }

        return $coreWebVitals;
    }

    public function getOptimizationSuggestions(?string $url = null, string $strategy = 'mobile'): Collection
    {
        $url = $url !== null && $url !== '' && $url !== '0' ? $url : $this->project->url;
        $analysis = $this->analyzeUrl($url, $strategy);

        $suggestions = new Collection();

        if (isset($analysis['lighthouse_result']['audits'])) {
            $audits = $analysis['lighthouse_result']['audits'];

            foreach ($audits as $auditKey => $audit) {
                // Only include audits that have suggestions and are not perfect scores
                if (isset($audit['score']) && $audit['score'] < 1 && isset($audit['title'])) {
                    $suggestions->push([
                        'audit_key' => $auditKey,
                        'title' => $audit['title'],
                        'description' => $audit['description'] ?? '',
                        'score' => $audit['score'],
                        'display_value' => $audit['displayValue'] ?? null,
                        'details' => $audit['details'] ?? null,
                    ]);
                }
            }
        }

        // Sort by score (worst first)
        return $suggestions->sortBy('score');
    }

    private function processPageSpeedData(array $data): array
    {
        $processed = [
            'url' => $data['id'] ?? '',
            'strategy' => $data['lighthouseResult']['configSettings']['emulatedFormFactor'] ?? 'mobile',
            'timestamp' => now()->toISOString(),
        ];

        // Overall scores
        if (isset($data['lighthouseResult']['categories'])) {
            $categories = $data['lighthouseResult']['categories'];

            $processed['scores'] = [
                'performance' => isset($categories['performance']) ? round($categories['performance']['score'] * 100) : null,
                'accessibility' => isset($categories['accessibility']) ? round($categories['accessibility']['score'] * 100) : null,
                'best_practices' => isset($categories['best-practices']) ? round($categories['best-practices']['score'] * 100) : null,
                'seo' => isset($categories['seo']) ? round($categories['seo']['score'] * 100) : null,
            ];
        }

        // Core Web Vitals metrics
        if (isset($data['lighthouseResult']['audits'])) {
            $processed['core_web_vitals'] = $this->getCoreWebVitals($data['id'], $processed['strategy']);
        }

        // Field data (if available)
        if (isset($data['loadingExperience']['metrics'])) {
            $processed['field_data'] = $data['loadingExperience']['metrics'];
        }

        // Store the full lighthouse result for detailed analysis
        $processed['lighthouse_result'] = $data['lighthouseResult'] ?? [];

        return $processed;
    }

    public function getHistoricalData(int $days = 30): Collection
    {
        // Note: PageSpeed Insights doesn't store historical data
        // You would need to implement your own storage mechanism
        // This is a placeholder for future implementation

        return new Collection();
    }

    private function storeResults(array $processed, array $rawData): void
    {
        $coreWebVitals = $processed['core_web_vitals'] ?? [];

        PageSpeedResult::query()->create([
            'project_id' => $this->project->id,
            'url' => $processed['url'],
            'strategy' => $processed['strategy'],
            'performance_score' => $processed['scores']['performance'] ?? null,
            'accessibility_score' => $processed['scores']['accessibility'] ?? null,
            'best_practices_score' => $processed['scores']['best_practices'] ?? null,
            'seo_score' => $processed['scores']['seo'] ?? null,
            'lcp_value' => $coreWebVitals['lcp']['value'] ?? null,
            'lcp_display' => $coreWebVitals['lcp']['display_value'] ?? null,
            'lcp_score' => $coreWebVitals['lcp']['score'] ?? null,
            'fcp_value' => $coreWebVitals['fcp']['value'] ?? null,
            'fcp_display' => $coreWebVitals['fcp']['display_value'] ?? null,
            'fcp_score' => $coreWebVitals['fcp']['score'] ?? null,
            'cls_value' => $coreWebVitals['cls']['value'] ?? null,
            'cls_display' => $coreWebVitals['cls']['display_value'] ?? null,
            'cls_score' => $coreWebVitals['cls']['score'] ?? null,
            'speed_index_value' => $coreWebVitals['speed_index']['value'] ?? null,
            'speed_index_display' => $coreWebVitals['speed_index']['display_value'] ?? null,
            'speed_index_score' => $coreWebVitals['speed_index']['score'] ?? null,
            'raw_data' => $rawData,
            'analyzed_at' => now(),
        ]);
    }
}
