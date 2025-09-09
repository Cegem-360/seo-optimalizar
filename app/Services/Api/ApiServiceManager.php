<?php

namespace App\Services\Api;

use App\Models\Project;
use Illuminate\Support\Collection;

class ApiServiceManager
{
    private Project $project;
    private array $services = [];

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getService(string $serviceName): BaseApiService
    {
        if (!isset($this->services[$serviceName])) {
            $this->services[$serviceName] = $this->createService($serviceName);
        }

        return $this->services[$serviceName];
    }

    private function createService(string $serviceName): BaseApiService
    {
        return match ($serviceName) {
            'google_search_console' => new GoogleSearchConsoleService($this->project),
            'google_analytics' => new GoogleAnalyticsService($this->project),
            'google_pagespeed_insights' => new PageSpeedInsightsService($this->project),
            'google_ads' => new GoogleAdsApiService($this->project),
            'gemini' => new GeminiApiService($this->project),
            default => throw new \InvalidArgumentException("Unknown service: {$serviceName}"),
        };
    }

    public function getGoogleSearchConsole(): GoogleSearchConsoleService
    {
        return $this->getService('google_search_console');
    }

    public function getGoogleAnalytics(): GoogleAnalyticsService
    {
        return $this->getService('google_analytics');
    }


    public function getPageSpeedInsights(): PageSpeedInsightsService
    {
        return $this->getService('google_pagespeed_insights');
    }

    public function getGoogleAds(): GoogleAdsApiService
    {
        return $this->getService('google_ads');
    }

    public function getGemini(): GeminiApiService
    {
        return $this->getService('gemini');
    }

    public function getConfiguredServices(): Collection
    {
        $configuredServices = collect();
        
        $availableServices = [
            'google_search_console' => 'Google Search Console',
            'google_analytics' => 'Google Analytics',
            'google_pagespeed_insights' => 'PageSpeed Insights',
            'google_ads' => 'Google Ads (Keyword Planner)',
            'gemini' => 'Google Gemini',
        ];

        foreach ($availableServices as $serviceKey => $serviceName) {
            try {
                $service = $this->getService($serviceKey);
                $configuredServices->push([
                    'key' => $serviceKey,
                    'name' => $serviceName,
                    'configured' => $service->isConfigured(),
                    'service' => $service,
                ]);
            } catch (\Exception $e) {
                $configuredServices->push([
                    'key' => $serviceKey,
                    'name' => $serviceName,
                    'configured' => false,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $configuredServices;
    }

    public function testAllConnections(): array
    {
        $results = [];
        $services = $this->getConfiguredServices();

        foreach ($services as $serviceInfo) {
            $serviceKey = $serviceInfo['key'];
            
            if (!$serviceInfo['configured']) {
                $results[$serviceKey] = [
                    'name' => $serviceInfo['name'],
                    'success' => false,
                    'message' => 'Service not configured',
                ];
                continue;
            }

            try {
                $service = $serviceInfo['service'];
                $success = $service->testConnection();
                
                $results[$serviceKey] = [
                    'name' => $serviceInfo['name'],
                    'success' => $success,
                    'message' => $success ? 'Connection successful' : 'Connection failed',
                ];
            } catch (\Exception $e) {
                $results[$serviceKey] = [
                    'name' => $serviceInfo['name'],
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function syncAllData(): array
    {
        $results = [];

        // Sync Search Console data
        try {
            if ($this->hasService('google_search_console')) {
                $gsc = $this->getGoogleSearchConsole();
                $synced = $gsc->syncKeywordRankings();
                $results['google_search_console'] = [
                    'success' => true,
                    'message' => "Synced {$synced} keywords from Google Search Console",
                    'count' => $synced,
                ];
            }
        } catch (\Exception $e) {
            $results['google_search_console'] = [
                'success' => false,
                'message' => 'Error syncing Search Console data: ' . $e->getMessage(),
            ];
        }


        return $results;
    }

    public function hasService(string $serviceName): bool
    {
        try {
            $service = $this->getService($serviceName);
            return $service->isConfigured();
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function forProject(Project $project): static
    {
        return new static($project);
    }
}