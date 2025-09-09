<?php

namespace App\Console\Commands\Google;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;

class GetServiceAccountInfo extends Command
{
    protected $signature = 'google:service-account-info';

    protected $description = 'Get Service Account Client ID and info for domain-wide delegation';

    /**
     * Create a new console command instance.
     */
    public function __construct(private readonly Filesystem $filesystem, private readonly Repository $repository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $credentialsPath = $this->repository->get('services.google.credentials_path');

        if (! $credentialsPath) {
            $this->error('GOOGLE_APPLICATION_CREDENTIALS not set in .env');

            return self::FAILURE;
        }

        $fullPath = base_path($credentialsPath);

        if (! $this->filesystem->exists($fullPath)) {
            $this->error('Service account file not found at: ' . $fullPath);
            $this->info('Run: php artisan google:setup-service-account');

            return self::FAILURE;
        }

        $json = json_decode((string) $this->filesystem->get($fullPath), true);

        if (! $json) {
            $this->error('Invalid JSON file');

            return self::FAILURE;
        }

        $this->info('Service Account Information:');
        $this->info('============================');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['Service Account Email', $json['client_email'] ?? 'N/A'],
                ['Client ID (for delegation)', $json['client_id'] ?? 'N/A'],
                ['Project ID', $json['project_id'] ?? 'N/A'],
                ['Private Key ID', substr($json['private_key_id'] ?? '', 0, 20) . '...'],
            ]
        );

        $this->newLine();
        $this->info('To enable Domain-Wide Delegation:');
        $this->info('1. Copy the Client ID above');
        $this->info('2. Go to: https://admin.google.com');
        $this->info('3. Security → API controls → Domain-wide delegation');
        $this->info('4. Add new → Paste Client ID');
        $this->info('5. Add these scopes:');
        $this->line('   https://www.googleapis.com/auth/webmasters.readonly');
        $this->line('   https://www.googleapis.com/auth/webmasters');
        $this->newLine();

        $this->info('Current impersonation email: ' . $this->repository->get('services.google.workspace_subject', 'Not set'));

        if (! $this->repository->get('services.google.workspace_subject')) {
            $this->warn('Add to .env: GOOGLE_WORKSPACE_SUBJECT="tamas@cegem360.com"');
        }

        return self::SUCCESS;
    }
}
