<?php

namespace App\Console\Commands\GoogleAds;

use Illuminate\Console\Command;
use Illuminate\Session\SessionManager;

class GenerateRefreshToken extends Command
{
    protected $signature = 'google-ads:generate-refresh-token 
                            {--client-id= : OAuth2 Client ID from Google Cloud Console}
                            {--client-secret= : OAuth2 Client Secret from Google Cloud Console}
                            {--redirect-uri= : Custom redirect URI (use ngrok URL or localhost)}';

    protected $description = 'Generate OAuth2 refresh token for Google Ads API';

    /**
     * Create a new console command instance.
     */
    public function __construct(private readonly SessionManager $sessionManager)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Google Ads API - OAuth2 Refresh Token Generator');
        $this->info('================================================');
        $this->newLine();

        // Get credentials
        $clientId = $this->option('client-id') ?? $this->ask('Enter your OAuth2 Client ID');
        $clientSecret = $this->option('client-secret') ?? $this->secret('Enter your OAuth2 Client Secret');

        // Get redirect URI
        $redirectUri = $this->option('redirect-uri');
        if (! $redirectUri) {
            $this->info('Choose redirect URI option:');
            $this->line('1. Use urn:ietf:wg:oauth:2.0:oob (copy code manually)');
            $this->line('2. Use ngrok URL (if running ngrok)');
            $this->line('3. Use localhost (if running php artisan serve)');
            $this->line('4. Enter custom URL');

            $choice = $this->ask('Enter choice (1-4)', '1');

            $redirectUri = match ($choice) {
                '1' => 'urn:ietf:wg:oauth:2.0:oob',
                '2' => $this->ask('Enter ngrok URL (e.g., https://abc123.ngrok.io/admin/google-ads/oauth/callback)'),
                '3' => 'http://localhost:8000/admin/google-ads/oauth/callback',
                '4' => $this->ask('Enter custom redirect URI'),
                default => 'urn:ietf:wg:oauth:2.0:oob'
            };
        }

        if (! $clientId || ! $clientSecret) {
            $this->error('Client ID and Client Secret are required!');

            return self::FAILURE;
        }

        // OAuth2 configuration
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $scope = 'https://www.googleapis.com/auth/adwords';

        // Generate authorization URL parameters
        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        // For web callback flows, add state parameter and store credentials in session
        if ($redirectUri !== 'urn:ietf:wg:oauth:2.0:oob') {
            $state = bin2hex(random_bytes(16));
            $params['state'] = $state;

            $this->sessionManager->put([
                'google_ads_oauth_client_id' => $clientId,
                'google_ads_oauth_client_secret' => $clientSecret,
                'google_ads_oauth_state' => $state,
            ]);

            $this->info('✅ OAuth credentials and state stored in session');
        }

        $authorizationUrl = $authUrl . '?' . http_build_query($params);

        if ($redirectUri === 'urn:ietf:wg:oauth:2.0:oob') {
            // Out-of-band flow
            $this->info('Step 1: Open this URL in your browser:');
            $this->newLine();
            $this->line($authorizationUrl);
            $this->newLine();

            $this->info('Step 2: Sign in with the Google account that has access to Google Ads');
            $this->info('Step 3: Grant permissions to the application');
            $this->info('Step 4: Copy the authorization code from the page');
            $this->newLine();

            $authCode = $this->ask('Enter the authorization code');
        } else {
            // Web callback flow
            $this->info('Step 1: Open this URL in your browser:');
            $this->newLine();
            $this->line($authorizationUrl);
            $this->newLine();

            $this->info('Step 2: Sign in and authorize the application');
            $this->info('Step 3: You will be redirected to the callback URL');
            $this->info('Step 4: Check the callback page for your refresh token');
            $this->newLine();

            $this->warn('💡 This process will use web callback. The refresh token will be displayed on the callback page.');
            $this->info('Press Ctrl+C to cancel or Enter to continue...');
            $this->ask('Press Enter when you have completed the OAuth flow and got your refresh token');

            return self::SUCCESS;
        }

        if (! $authCode) {
            $this->error('Authorization code is required!');

            return self::FAILURE;
        }

        $this->info('Exchanging authorization code for refresh token...');

        // Exchange authorization code for tokens
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $authCode,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->error('Failed to get refresh token!');
            $this->error('Response: ' . $response);

            return self::FAILURE;
        }

        $tokens = json_decode($response, true);

        if (isset($tokens['refresh_token'])) {
            $this->newLine();
            $this->info('✅ Success! Here are your tokens:');
            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['Refresh Token', $tokens['refresh_token']],
                    ['Access Token', $tokens['access_token'] ?? 'N/A'],
                    ['Expires In', ($tokens['expires_in'] ?? 0) . ' seconds'],
                ]
            );

            $this->newLine();
            $this->info('📋 Save these credentials in your API Credentials:');
            $this->line('client_id: ' . $clientId);
            $this->line('client_secret: [your client secret]');
            $this->line('refresh_token: ' . $tokens['refresh_token']);
            $this->line('developer_token: [get from Google Ads API Center]');
            $this->line('customer_id: [your Google Ads customer ID without dashes]');

            $this->newLine();
            $this->warn('⚠️  Keep the refresh token secure! It provides access to your Google Ads account.');

            return self::SUCCESS;
        }

        $this->error('No refresh token in response!');
        $this->error('Response: ' . $response);

        return self::FAILURE;
    }
}
