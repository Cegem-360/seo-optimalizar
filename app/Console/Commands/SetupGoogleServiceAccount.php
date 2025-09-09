<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupGoogleServiceAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:setup-gsc {project=1 : Project ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manual setup for Google Search Console credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->argument('project');
        $project = \App\Models\Project::query()->find($projectId);

        if (! $project) {
            $this->error(sprintf('Project %s not found', $projectId));

            return 1;
        }

        $this->info('Setting up Google Search Console for: ' . $project->name);
        $this->info('Please follow these steps:');
        $this->newLine();

        $this->line('1. Go to Google Cloud Console');
        $this->line('2. Create OAuth 2.0 credentials');
        $this->line('3. Add these redirect URIs:');
        $this->line('   - https://seo-optimalizer.test/auth/google/callback');
        $this->line('   - http://seo-optimalizer.test/auth/google/callback');
        $this->newLine();

        $clientId = $this->ask('Enter your Client ID');
        $clientSecret = $this->ask('Enter your Client Secret');

        $this->info('Now we need a refresh token. Visit this URL in your browser:');
        $this->newLine();

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => 'https://seo-optimalizer.test/auth/google/callback',
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly email profile',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        $this->line($authUrl);
        $this->newLine();

        $refreshToken = $this->ask('Enter the refresh token you received');

        // Save to database
        $credentials = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
        ];

        $apiCredential = $project->apiCredentials()
            ->where('service', 'google_search_console')
            ->first();

        if ($apiCredential) {
            $apiCredential->update([
                'credentials' => $credentials,
                'is_active' => true,
            ]);
            $this->info('Updated existing credentials');
        } else {
            \App\Models\ApiCredential::query()->create([
                'project_id' => $project->id,
                'service' => 'google_search_console',
                'credentials' => $credentials,
                'is_active' => true,
            ]);
            $this->info('Created new credentials');
        }

        $this->newLine();
        $this->info('✅ Google Search Console credentials saved successfully!');
        $this->line('Test with: php artisan seo:test-api ' . $projectId);

        return 0;
    }
}
