<?php

namespace App\Services;

use Exception;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;

class GoogleAdsOAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/adwords';

    public function __construct(private readonly Repository $repository) {}

    public function generateAuthUrl(string $clientId, string $redirectUri, Request $request): string
    {
        $state = bin2hex(random_bytes(16));
        // Store in cache instead of session to avoid domain issues
        $this->repository->put('google_ads_oauth_state_' . $state, $state, now()->addMinutes(10));
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

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    public function exchangeCodeForToken(string $code, string $clientId, string $clientSecret, string $redirectUri): array
    {
        // Use cURL directly to avoid facade dependency issues
        $postData = http_build_query([
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => self::TOKEN_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen($postData),
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error !== '' && $error !== '0') {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('Failed to exchange code for token. HTTP ' . $httpCode . ': ' . $response);
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . json_last_error_msg());
        }

        if (! isset($data['refresh_token'])) {
            throw new Exception('No refresh token in response: ' . json_encode($data));
        }

        return $data;
    }

    public function validateState(string $state, Request $request): bool
    {
        // Check cache first (primary method)
        $cacheKey = 'google_ads_oauth_state_' . $state;
        $cachedState = $this->repository->get($cacheKey);

        if ($cachedState && $cachedState === $state) {
            $this->repository->forget($cacheKey);
            $request->session()->forget('google_ads_oauth_state');

            return true;
        }

        // Fallback to session (if cache fails)
        $sessionState = $request->session()->get('google_ads_oauth_state');
        $request->session()->forget('google_ads_oauth_state');

        return $sessionState && $sessionState === $state;
    }
}
