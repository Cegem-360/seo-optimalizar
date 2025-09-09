<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    /**
     * Rate limits for different API services
     */
    private const SERVICE_LIMITS = [
        'google_search_console' => ['requests' => 100, 'per' => 'hour'],
        'google_analytics' => ['requests' => 100, 'per' => 'hour'],
        'serpapi' => ['requests' => 100, 'per' => 'month'], // SerpAPI is usually limited monthly
        'google_pagespeed_insights' => ['requests' => 25, 'per' => 'day'],
    ];

    private array $serviceLimits = self::SERVICE_LIMITS;

    public function __construct(private readonly ResponseFactory $responseFactory) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $service = null): Response
    {
        if ($service === null || $service === '' || $service === '0' || ! isset($this->serviceLimits[$service])) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request, $service);
        $limit = $this->serviceLimits[$service];

        $response = RateLimiter::attempt(
            $key,
            $limit['requests'],
            fn () => $next($request),
            $this->getDecaySeconds($limit['per'])
        );

        if (! $response) {
            return $this->responseFactory->json([
                'error' => 'Too many API requests for ' . $service,
                'retry_after' => RateLimiter::availableIn($key),
                'limit' => $limit['requests'],
                'period' => $limit['per'],
            ], 429);
        }

        return $response;
    }

    /**
     * Resolve the rate limiting key for the request
     */
    protected function resolveRequestSignature(Request $request, string $service): string
    {
        $projectId = $request->route('project') ?? 'global';

        return sprintf('api-rate-limit:%s:%s', $service, $projectId);
    }

    /**
     * Get decay time in seconds for different periods
     */
    protected function getDecaySeconds(string $period): int
    {
        return match ($period) {
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
            'month' => 2592000, // 30 days
            default => 3600, // Default to 1 hour
        };
    }

    /**
     * Check if service has remaining quota
     */
    public static function hasQuota(string $service, ?int $projectId = null): bool
    {
        $key = sprintf('api-rate-limit:%s:', $service) . ($projectId ?? 'global');

        if (! isset(self::SERVICE_LIMITS[$service])) {
            return true;
        }

        $limit = self::SERVICE_LIMITS[$service];

        return RateLimiter::remaining($key, $limit['requests']) > 0;
    }

    /**
     * Get remaining quota for a service
     */
    public static function getRemainingQuota(string $service, ?int $projectId = null): int
    {
        $key = sprintf('api-rate-limit:%s:', $service) . ($projectId ?? 'global');

        if (! isset(self::SERVICE_LIMITS[$service])) {
            return PHP_INT_MAX;
        }

        $limit = self::SERVICE_LIMITS[$service];

        return RateLimiter::remaining($key, $limit['requests']);
    }

    /**
     * Get service rate limit information
     */
    public static function getServiceLimits(): array
    {
        return self::SERVICE_LIMITS;
    }
}
