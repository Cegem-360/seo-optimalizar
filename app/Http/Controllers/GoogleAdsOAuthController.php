<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GoogleAdsOAuthService;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;

class GoogleAdsOAuthController extends Controller
{
    public function __construct(private readonly Repository $repository, private readonly ResponseFactory $responseFactory, private readonly Redirector $redirector) {}

    private function renderOAuthResult(array $data): Response
    {
        try {
            return $this->responseFactory->view('google-ads-oauth-result', $data);
        } catch (Exception) {
            // Fallback to basic HTML if view system fails
            $html = '<!DOCTYPE html>
<html>
<head>
    <title>Google Ads OAuth Result</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; }
        .debug { background: #f8f9fa; color: #495057; padding: 10px; margin: 10px 0; border-left: 3px solid #007bff; font-family: monospace; font-size: 12px; }
        pre { white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body>';

            if ($data['success'] ?? false) {
                $html .= '<div class="success">
                    <h2>✅ Success!</h2>
                    <p>' . htmlspecialchars($data['message'] ?? 'OAuth completed successfully') . '</p>';
                if (isset($data['refreshToken'])) {
                    $html .= '<p><strong>Refresh Token:</strong> ' . htmlspecialchars($data['refreshToken']) . '</p>';
                }

                $html .= '</div>';
            } else {
                $html .= '<div class="error">
                    <h2>❌ Error</h2>
                    <p>' . htmlspecialchars($data['error'] ?? 'Unknown error') . '</p>';
                if (isset($data['debug'])) {
                    $html .= '<div class="debug">
                        <strong>Debug Info:</strong><br>
                        File: ' . htmlspecialchars($data['debug']['file'] ?? 'N/A') . '<br>
                        Line: ' . htmlspecialchars($data['debug']['line'] ?? 'N/A') . '<br>
                        <pre>' . htmlspecialchars(substr($data['debug']['trace'] ?? '', 0, 2000)) . '</pre>
                    </div>';
                }

                $html .= '</div>';
            }

            $html .= '<p><button onclick="window.close()">Close Window</button></p>
</body>
</html>';

            return $this->responseFactory->make($html, 200, ['Content-Type' => 'text/html']);
        }
    }

    public function start(Request $request, GoogleAdsOAuthService $googleAdsOAuthService)
    {
        // Get client credentials from request or session
        $clientId = $request->get('client_id') ?? $request->session()->get('google_ads_oauth_client_id');
        $clientSecret = $request->get('client_secret') ?? $request->session()->get('google_ads_oauth_client_secret');

        if (! $clientId || ! $clientSecret) {
            return $this->redirector->to('/admin/api-credentials')->with('error',
                'Google Ads client credentials are required to start OAuth process',
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

        $authUrl = $googleAdsOAuthService->generateAuthUrl($clientId, $redirectUri, $request);

        // Debug: check if state is in URL
        if (! str_contains($authUrl, 'state=')) {
            throw new Exception('State parameter missing from OAuth URL: ' . $authUrl);
        }

        // Redirect to Google OAuth
        return $this->redirector->to($authUrl);
    }

    public function callback(Request $request, GoogleAdsOAuthService $googleAdsOAuthService): Response
    {
        try {
            // Check for errors
            if ($request->has('error')) {
                return $this->renderOAuthResult([
                    'success' => false,
                    'error' => $request->get('error_description', 'OAuth authorization was denied'),
                ]);
            }

            // Validate state
            $receivedState = $request->get('state');
            $savedState = $request->session()->get('google_ads_oauth_state');

            if (! $request->has('state') || ! $googleAdsOAuthService->validateState($receivedState, $request)) {
                return $this->renderOAuthResult([
                    'success' => false,
                    'error' => sprintf('Invalid state parameter. Received: %s, Saved: %s. Please try again.', $receivedState, $savedState),
                ]);
            }

            // Get the authorization code
            $code = $request->get('code');
            if (! $code) {
                return $this->renderOAuthResult([
                    'success' => false,
                    'error' => 'No authorization code received',
                ]);
            }

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

            return $this->renderOAuthResult([
                'success' => true,
                'refreshToken' => $tokens['refresh_token'],
                'message' => 'Successfully generated refresh token! You can now close this window and return to the form.',
            ]);
        } catch (Exception $exception) {
            // Log the full error for debugging
            Log::error('OAuth callback error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
                'request_params' => $request->all(),
            ]);

            return $this->renderOAuthResult([
                'success' => false,
                'error' => 'Application error during OAuth callback: ' . $exception->getMessage(),
                'debug' => [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => substr($exception->getTraceAsString(), 0, 1000), // Limit trace length
                ],
            ]);
        }
    }
}
