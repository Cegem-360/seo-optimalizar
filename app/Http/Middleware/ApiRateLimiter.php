<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    /**
     * Rate limits for different API services
     */
    private array $serviceLimits = [
        'google_search_console' => ['requests' => 100, 'per' => 'hour'],
        'google_analytics' => ['requests' => 100, 'per' => 'hour'],
        'serpapi' => ['requests' => 100, 'per' => 'month'], // SerpAPI is usually limited monthly
        'google_pagespeed_insights' => ['requests' => 25, 'per' => 'day'],
    ];

    public function __construct(private readonly \Illuminate\Contracts\Routing\ResponseFactory $responseFactory) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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
        $static = new static();
        $key = sprintf('api-rate-limit:%s:', $service) . ($projectId ?? 'global');

        if (! isset($static->serviceLimits[$service])) {
            return true;
        }

        $limit = $static->serviceLimits[$service];

        return RateLimiter::remaining($key, $limit['requests']) > 0;
    }

    /**
     * Get remaining quota for a service
     */
    public static function getRemainingQuota(string $service, ?int $projectId = null): int
    {
        $static = new static();
        $key = sprintf('api-rate-limit:%s:', $service) . ($projectId ?? 'global');

        if (! isset($static->serviceLimits[$service])) {
            return PHP_INT_MAX;
        }

        $limit = $static->serviceLimits[$service];

        return RateLimiter::remaining($key, $limit['requests']);
    }

    /**
     * Get service rate limit information
     */
    public static function getServiceLimits(): array
    {
        return (new static())->serviceLimits;
    }
}
