<?php

declare(strict_types=1);

namespace App\Console\Commands\Google;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class SetupGoogleServiceAccountNew extends Command
{
    protected $signature = 'google:setup-service-account';

    protected $description = 'Setup Google Service Account for Search Console API access (no OAuth needed)';

    /**
     * Create a new console command instance.
     */
    public function __construct(private readonly Filesystem $filesystem)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Google Search Console Service Account Setup');
        $this->info('==========================================');
        $this->newLine();

        $this->info('This method uses Service Account authentication - NO OAuth redirect needed!');
        $this->newLine();

        $this->info('Follow these steps to setup Service Account authentication:');
        $this->newLine();

        $this->comment('1. Go to Google Cloud Console:');
        $this->line('   https://console.cloud.google.com/');
        $this->newLine();

        $this->comment('2. Create a new project or select existing one');
        $this->newLine();

        $this->comment('3. Enable Search Console API:');
        $this->line('   - Go to "APIs & Services" > "Library"');
        $this->line('   - Search for "Google Search Console API"');
        $this->line('   - Click "Enable"');
        $this->newLine();

        $this->comment('4. Create Service Account:');
        $this->line('   - Go to "IAM & Admin" > "Service Accounts"');
        $this->line('   - Click "Create Service Account"');
        $this->line('   - Give it a name (e.g., "seo-optimizer-service")');
        $this->line('   - Click "Create and Continue"');
        $this->line('   - Skip the optional steps');
        $this->newLine();

        $this->comment('5. Create and download JSON key:');
        $this->line('   - Click on the created service account');
        $this->line('   - Go to "Keys" tab');
        $this->line('   - Click "Add Key" > "Create new key"');
        $this->line('   - Choose "JSON" format');
        $this->line('   - Save the file');
        $this->newLine();

        $this->comment('6. Add Service Account to Search Console:');
        $this->line('   - Note the service account email (e.g., name@project.iam.gserviceaccount.com)');
        $this->line('   - Go to https://search.google.com/search-console');
        $this->line('   - Select your property');
        $this->line('   - Go to Settings > Users and permissions');
        $this->line('   - Click "Add user"');
        $this->line('   - Enter the service account email');
        $this->line('   - Set permission to "Full" or "Restricted" as needed');
        $this->newLine();

        if ($this->confirm('Do you have the JSON key file ready?')) {
            $path = $this->ask('Enter the full path to your JSON key file');

            if (! file_exists($path)) {
                $this->error('File not found: ' . $path);

                return self::FAILURE;
            }

            // Validate JSON structure
            $content = file_get_contents($path);
            $json = json_decode($content, true);

            if (! $json || ! isset($json['type']) || $json['type'] !== 'service_account') {
                $this->error('Invalid service account JSON file');

                return self::FAILURE;
            }

            // Copy to storage
            $targetPath = storage_path('app/google-service-account.json');
            $this->filesystem->ensureDirectoryExists(dirname($targetPath));
            $this->filesystem->copy($path, $targetPath);

            $this->info('✓ Service account credentials saved to: ' . $targetPath);
            $this->info('✓ Environment variable GOOGLE_APPLICATION_CREDENTIALS is already set');
            $this->newLine();

            // Test the connection
            if ($this->confirm('Would you like to test the connection?')) {
                $this->call('google:test-connection');
            }
        } else {
            $this->info('Once you have the JSON file, run this command again or manually:');
            $this->info('1. Place the JSON file at: storage/app/google-service-account.json');
            $this->info('2. Ensure GOOGLE_APPLICATION_CREDENTIALS="storage/app/google-service-account.json" is in your .env file');
        }

        return self::SUCCESS;
    }
}
