<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class GoogleAdsOAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/adwords';

    public function generateAuthUrl(string $clientId, string $redirectUri, Request $request): string
    {
        $state = bin2hex(random_bytes(16));
        // Store in cache instead of session to avoid domain issues
        Cache::put("google_ads_oauth_state_{$state}", $state, now()->addMinutes(10));
        $request->session()->put('google_ads_oauth_state', $state);

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => self::SCOPE,
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];

        $url = self::AUTH_URL . '?' . http_build_query($params);
        
        // Debug log
        \Log::info('Generated OAuth URL', [
            'url' => $url,
            'state' => $state,
            'params' => $params
        ]);

        return $url;
    }

    public function exchangeCodeForToken(string $code, string $clientId, string $clientSecret, string $redirectUri): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            throw new Exception('Failed to exchange code for token: ' . $response->body());
        }

        $data = $response->json();

        if (! isset($data['refresh_token'])) {
            throw new Exception('No refresh token in response');
        }

        return $data;
    }

    public function validateState(string $state, Request $request): bool
    {
        // Check cache first (primary method)
        $cacheKey = "google_ads_oauth_state_{$state}";
        $cachedState = Cache::get($cacheKey);
        
        if ($cachedState && $cachedState === $state) {
            Cache::forget($cacheKey);
            $request->session()->forget('google_ads_oauth_state');
            return true;
        }
        
        // Fallback to session (if cache fails)
        $sessionState = $request->session()->get('google_ads_oauth_state');
        $request->session()->forget('google_ads_oauth_state');

        return $sessionState && $sessionState === $state;
    }
}
