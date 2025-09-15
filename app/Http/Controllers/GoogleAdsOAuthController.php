<?php

namespace App\Http\Controllers;

use App\Services\GoogleAdsOAuthService;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;

class GoogleAdsOAuthController extends Controller
{
    public function __construct(private readonly Redirector $redirector, private readonly Repository $repository, private readonly Factory $factory) {}

    public function start(Request $request, GoogleAdsOAuthService $googleAdsOAuthService)
    {
        // Get client credentials from request or session
        $clientId = $request->get('client_id') ?? $request->session()->get('google_ads_oauth_client_id');
        $clientSecret = $request->get('client_secret') ?? $request->session()->get('google_ads_oauth_client_secret');

        if (! $clientId || ! $clientSecret) {
            return $this->redirector->to('/admin/api-credentials')->with('error',
                'Google Ads client credentials are required to start OAuth process'
            );
        }

        // Store credentials in session for the callback
        $request->session()->put([
            'google_ads_oauth_client_id' => $clientId,
            'google_ads_oauth_client_secret' => $clientSecret,
        ]);

        // Generate OAuth URL - prioritize HERD_SHARE_URL if set
        $shareUrl = $this->repository->get('app.herd_share_url');
        if ($shareUrl) {
            // Use the share URL if configured
            $redirectUri = rtrim((string) $shareUrl, '/') . '/admin/google-ads/oauth/callback';
        } else {
            // Fall back to the regular app URL
            $baseUrl = $this->repository->get('app.url');
            $redirectUri = rtrim((string) $baseUrl, '/') . '/admin/google-ads/oauth/callback';
        }

        // Debug: Log the redirect URI being used
        Log::info('OAuth start - Redirect URI', [
            'app_url' => $this->repository->get('app.url'),
            'share_url' => $shareUrl,
            'redirect_uri' => $redirectUri,
        ]);

        $authUrl = $googleAdsOAuthService->generateAuthUrl($clientId, $redirectUri, $request);

        // Debug: check if state is in URL
        if (! str_contains($authUrl, 'state=')) {
            throw new Exception('State parameter missing from OAuth URL: ' . $authUrl);
        }

        // Redirect to Google OAuth
        return $this->redirector->to($authUrl);
    }

    public function callback(Request $request, GoogleAdsOAuthService $googleAdsOAuthService)
    {
        // Debug: log all incoming parameters
        Log::info('OAuth callback received', [
            'all_params' => $request->all(),
            'state_param' => $request->get('state'),
            'code_param' => $request->get('code'),
            'error_param' => $request->get('error'),
        ]);
        // Check for errors
        if ($request->has('error')) {
            return $this->factory->make('google-ads-oauth-result', [
                'success' => false,
                'error' => $request->get('error_description', 'OAuth authorization was denied'),
            ]);
        }

        // Validate state
        $receivedState = $request->get('state');
        $savedState = $request->session()->get('google_ads_oauth_state');

        if (! $request->has('state') || ! $googleAdsOAuthService->validateState($receivedState, $request)) {
            return $this->factory->make('google-ads-oauth-result', [
                'success' => false,
                'error' => sprintf('Invalid state parameter. Received: %s, Saved: %s. Please try again.', $receivedState, $savedState),
            ]);
        }

        // Get the authorization code
        $code = $request->get('code');
        if (! $code) {
            return $this->factory->make('google-ads-oauth-result', [
                'success' => false,
                'error' => 'No authorization code received',
            ]);
        }

        try {
            // Get stored credentials from session
            $clientId = $request->session()->get('google_ads_oauth_client_id');
            $clientSecret = $request->session()->get('google_ads_oauth_client_secret');

            if (! $clientId || ! $clientSecret) {
                throw new Exception('OAuth credentials not found in session. Please start the process again.');
            }

            // Exchange code for tokens - use same redirect URI logic
            $shareUrl = $this->repository->get('app.herd_share_url');
            if ($shareUrl) {
                // Use the share URL if configured
                $redirectUri = rtrim((string) $shareUrl, '/') . '/admin/google-ads/oauth/callback';
            } else {
                // Fall back to the regular app URL
                $baseUrl = $this->repository->get('app.url');
                $redirectUri = rtrim((string) $baseUrl, '/') . '/admin/google-ads/oauth/callback';
            }

            $tokens = $googleAdsOAuthService->exchangeCodeForToken($code, $clientId, $clientSecret, $redirectUri);

            // Store tokens in session for the form
            $request->session()->put([
                'google_ads_refresh_token' => $tokens['refresh_token'],
                'google_ads_access_token' => $tokens['access_token'] ?? null,
            ]);

            // Clean up
            $request->session()->forget(['google_ads_oauth_client_id', 'google_ads_oauth_client_secret']);

            return $this->factory->make('google-ads-oauth-result', [
                'success' => true,
                'refreshToken' => $tokens['refresh_token'],
                'message' => 'Successfully generated refresh token! You can now close this window and return to the form.',
            ]);
        } catch (Exception $exception) {
            return $this->factory->make('google-ads-oauth-result', [
                'success' => false,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
