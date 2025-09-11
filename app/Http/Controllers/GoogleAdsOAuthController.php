<?php

namespace App\Http\Controllers;

use App\Services\GoogleAdsOAuthService;
use Illuminate\Http\Request;

class GoogleAdsOAuthController extends Controller
{
    public function start(Request $request, GoogleAdsOAuthService $oauthService)
    {
        // Get client credentials from request or session
        $clientId = $request->get('client_id') ?? $request->session()->get('google_ads_oauth_client_id');
        $clientSecret = $request->get('client_secret') ?? $request->session()->get('google_ads_oauth_client_secret');

        if (!$clientId || !$clientSecret) {
            return redirect('/admin/api-credentials')->with('error', 
                'Google Ads client credentials are required to start OAuth process'
            );
        }

        // Store credentials in session for the callback
        $request->session()->put([
            'google_ads_oauth_client_id' => $clientId,
            'google_ads_oauth_client_secret' => $clientSecret,
        ]);

        // Generate OAuth URL - use share URL for .test domains
        $baseUrl = config('app.url');
        if (str_contains($baseUrl, '.test')) {
            // If using Herd share, you can set HERD_SHARE_URL in .env
            $shareUrl = config('app.herd_share_url');
            if ($shareUrl) {
                $redirectUri = rtrim($shareUrl, '/') . '/admin/google-ads/oauth/callback';
            } else {
                $redirectUri = url('/admin/google-ads/oauth/callback');
            }
        } else {
            $redirectUri = url('/admin/google-ads/oauth/callback');
        }
        
        $authUrl = $oauthService->generateAuthUrl($clientId, $redirectUri, $request);

        // Debug: check if state is in URL
        if (!str_contains($authUrl, 'state=')) {
            throw new \Exception('State parameter missing from OAuth URL: ' . $authUrl);
        }

        // Redirect to Google OAuth
        return redirect($authUrl);
    }

    public function callback(Request $request, GoogleAdsOAuthService $oauthService)
    {
        // Debug: log all incoming parameters
        \Log::info('OAuth callback received', [
            'all_params' => $request->all(),
            'state_param' => $request->get('state'),
            'code_param' => $request->get('code'),
            'error_param' => $request->get('error'),
        ]);
        // Check for errors
        if ($request->has('error')) {
            return view('google-ads-oauth-result', [
                'success' => false,
                'error' => $request->get('error_description', 'OAuth authorization was denied'),
            ]);
        }

        // Validate state
        $receivedState = $request->get('state');
        $savedState = $request->session()->get('google_ads_oauth_state');
        
        if (! $request->has('state') || ! $oauthService->validateState($receivedState, $request)) {
            return view('google-ads-oauth-result', [
                'success' => false,
                'error' => "Invalid state parameter. Received: {$receivedState}, Saved: {$savedState}. Please try again.",
            ]);
        }

        // Get the authorization code
        $code = $request->get('code');
        if (! $code) {
            return view('google-ads-oauth-result', [
                'success' => false,
                'error' => 'No authorization code received',
            ]);
        }

        try {
            // Get stored credentials from session
            $clientId = $request->session()->get('google_ads_oauth_client_id');
            $clientSecret = $request->session()->get('google_ads_oauth_client_secret');

            if (! $clientId || ! $clientSecret) {
                throw new \Exception('OAuth credentials not found in session. Please start the process again.');
            }

            // Exchange code for tokens - use same redirect URI logic
            $baseUrl = config('app.url');
            if (str_contains($baseUrl, '.test')) {
                $shareUrl = config('app.herd_share_url');
                if ($shareUrl) {
                    $redirectUri = rtrim($shareUrl, '/') . '/admin/google-ads/oauth/callback';
                } else {
                    $redirectUri = url('/admin/google-ads/oauth/callback');
                }
            } else {
                $redirectUri = url('/admin/google-ads/oauth/callback');
            }
            
            $tokens = $oauthService->exchangeCodeForToken($code, $clientId, $clientSecret, $redirectUri);

            // Store tokens in session for the form
            $request->session()->put([
                'google_ads_refresh_token' => $tokens['refresh_token'],
                'google_ads_access_token' => $tokens['access_token'] ?? null,
            ]);

            // Clean up
            $request->session()->forget(['google_ads_oauth_client_id', 'google_ads_oauth_client_secret']);

            return view('google-ads-oauth-result', [
                'success' => true,
                'refreshToken' => $tokens['refresh_token'],
                'message' => 'Successfully generated refresh token! You can now close this window and return to the form.',
            ]);
        } catch (\Exception $e) {
            return view('google-ads-oauth-result', [
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
