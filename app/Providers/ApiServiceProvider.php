<?php

namespace App\Providers;

use App\Services\Api\ApiServiceManager;
use App\Services\Api\GoogleAnalyticsService;
use App\Services\Api\GoogleSearchConsoleService;
use App\Services\Api\PageSpeedInsightsService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Create a new service provider instance.
     *
     * @param  Application  $app
     */
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register API services as singletons within project context
        $this->app->bind(GoogleSearchConsoleService::class, fn ($app, $parameters): GoogleSearchConsoleService => new GoogleSearchConsoleService($parameters['project']));

        $this->app->bind(GoogleAnalyticsService::class, fn ($app, $parameters): GoogleAnalyticsService => new GoogleAnalyticsService($parameters['project']));

        $this->app->bind(PageSpeedInsightsService::class, fn ($app, $parameters): PageSpeedInsightsService => new PageSpeedInsightsService($parameters['project']));

        $this->app->bind(ApiServiceManager::class, fn ($app, $parameters): ApiServiceManager => new ApiServiceManager($parameters['project']));
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
        $this->app->extend(HttpClientFactory::class, fn (HttpClientFactory $httpClientFactory) =>
            // Set default timeout and retry configuration for API requests
            $httpClientFactory->macro('apiRequest', fn () => $httpClientFactory->timeout(30)
                ->retry(3, 1000)
                ->withOptions([
                    'verify' => true,
                    'http_errors' => false, // Handle errors manually
                ])
                ->beforeSending(function ($request): void {
                    // Log API requests in debug mode
                    if (config('app.debug')) {
                        Log::debug('API Request', [
                            'url' => $request->url(),
                            'method' => $request->method(),
                            'headers' => $request->headers(),
                        ]);
                    }
                })));
    }
}
