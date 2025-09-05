<?php

namespace App\Providers;

use App\Services\Api\ApiServiceManager;
use App\Services\Api\GoogleAnalyticsService;
use App\Services\Api\GoogleSearchConsoleService;
use App\Services\Api\PageSpeedInsightsService;
use App\Services\Api\SerpApiService;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register API services as singletons within project context
        $this->app->bind(GoogleSearchConsoleService::class, function ($app, $parameters) {
            return new GoogleSearchConsoleService($parameters['project']);
        });

        $this->app->bind(GoogleAnalyticsService::class, function ($app, $parameters) {
            return new GoogleAnalyticsService($parameters['project']);
        });

        $this->app->bind(SerpApiService::class, function ($app, $parameters) {
            return new SerpApiService($parameters['project']);
        });

        $this->app->bind(PageSpeedInsightsService::class, function ($app, $parameters) {
            return new PageSpeedInsightsService($parameters['project']);
        });

        $this->app->bind(ApiServiceManager::class, function ($app, $parameters) {
            return new ApiServiceManager($parameters['project']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure HTTP Client defaults for API services
        $this->configureHttpClient();
    }

    private function configureHttpClient(): void
    {
        $this->app->extend(HttpClientFactory::class, function (HttpClientFactory $factory) {
            // Set default timeout and retry configuration for API requests
            return $factory->macro('apiRequest', function () use ($factory) {
                return $factory->timeout(30)
                    ->retry(3, 1000)
                    ->withOptions([
                        'verify' => true,
                        'http_errors' => false, // Handle errors manually
                    ])
                    ->beforeSending(function ($request, $options) {
                        // Log API requests in debug mode
                        if (config('app.debug')) {
                            Log::debug('API Request', [
                                'url' => $request->url(),
                                'method' => $request->method(),
                                'headers' => $request->headers(),
                            ]);
                        }
                    });
            });
        });
    }
}
