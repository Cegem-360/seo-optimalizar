<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\ApiCredential;
use App\Models\Project;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseApiService
{
    protected ?ApiCredential $credentials = null;

    protected string $serviceName;

    protected int $retryAttempts = 3;

    protected int $retryDelayMs = 1000;

    public function __construct(protected Project $project)
    {
        $this->loadCredentials();
    }

    protected function loadCredentials(): void
    {
        /** @var ApiCredential|null $credentials */
        $credentials = $this->project
            ->apiCredentials()
            ->where('service', $this->serviceName)
            ->where('is_active', true)
            ->first();

        $this->credentials = $credentials;
    }

    protected function makeRequest(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new Exception('API credentials not configured for service: ' . $this->serviceName);
        }

        return Http::retry($this->retryAttempts, $this->retryDelayMs)
            ->timeout(30)
            ->withOptions([
                'verify' => true,
            ])
            ->beforeSending(function (PendingRequest $pendingRequest): void {
                $this->configureRequest($pendingRequest);
            });
    }

    abstract protected function configureRequest(PendingRequest $pendingRequest): void;

    protected function handleResponse(Response $response): array
    {
        $this->markCredentialsAsUsed();

        if (! $response->successful()) {
            throw new Exception(
                sprintf('API request failed for %s: %d - %s', $this->serviceName, $response->status(), $response->body()),
            );
        }

        return $response->json() ?? [];
    }

    protected function markCredentialsAsUsed(): void
    {
        if ($this->credentials instanceof ApiCredential) {
            $this->credentials->markAsUsed();
        }
    }

    protected function getCredential(string $key): mixed
    {
        return $this->credentials?->getCredential($key);
    }

    abstract public function testConnection(): bool;

    public function isConfigured(): bool
    {
        return $this->credentials instanceof ApiCredential && $this->credentials->is_active;
    }
}
