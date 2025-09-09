<?php

namespace App\Console\Commands\Google;

use App\Services\GoogleSearchConsoleService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

class TestGoogleConnection extends Command
{
    protected $signature = 'google:test-connection';

    protected $description = 'Test Google Search Console API connection';

    /**
     * Create a new console command instance.
     */
    public function __construct(private readonly Repository $repository)
    {
        parent::__construct();
    }

    public function handle(GoogleSearchConsoleService $googleSearchConsoleService): int
    {
        $this->info('Testing Google Search Console API connection...');
        $this->newLine();

        // Check if credentials are configured
        if (! $googleSearchConsoleService->hasCredentials()) {
            $this->error('No credentials configured!');
            $this->info('Run: php artisan google:setup-service-account');

            return self::FAILURE;
        }

        // Check authentication type
        if ($googleSearchConsoleService->isUsingServiceAccount()) {
            $this->info('✓ Using Service Account authentication');
        } else {
            $this->info('✓ Using OAuth authentication');
        }

        // Try to list sites
        try {
            $this->info('Fetching available sites...');
            $sites = $googleSearchConsoleService->getSites();

            if ($sites === []) {
                $this->warn('No sites found. Make sure the service account has access to at least one Search Console property.');
                $this->info('Add the service account email to your Search Console property:');
                $this->info('1. Go to https://search.google.com/search-console');
                $this->info('2. Select your property');
                $this->info('3. Go to Settings > Users and permissions');
                $this->info('4. Add the service account email as a user');
            } else {
                $this->info('✓ Connection successful!');
                $this->newLine();
                $this->info('Available sites:');
                foreach ($sites as $site) {
                    $this->line(' - ' . $site->getSiteUrl());
                }
            }

            return self::SUCCESS;
        } catch (Exception $exception) {
            $this->error('Connection failed: ' . $exception->getMessage());
            $this->newLine();

            if (str_contains($exception->getMessage(), 'Could not load the default credentials')) {
                $this->info('Service Account file might be missing or invalid.');
                $this->info('Check that the file exists at: ' . base_path($this->repository->get('services.google.credentials_path')));
            } elseif (str_contains($exception->getMessage(), '403')) {
                $this->info('Access denied. Make sure:');
                $this->info('1. Search Console API is enabled in Google Cloud Console');
                $this->info('2. Service account has access to your Search Console property');
            }

            return self::FAILURE;
        }
    }
}
