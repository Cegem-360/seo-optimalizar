<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory => view('welcome'));

// Google OAuth2 flow for Search Console
Route::get('/auth/google', function () {
    $clientId = config('services.google.client_id');
    $redirectUri = config('services.google.redirect_uri');

    // Debug információ - mutassuk meg mi lesz elküldve
    if (request()->has('debug')) {
        return response()->json([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'app_url' => config('app.url'),
            'note' => 'Add these redirect URIs to Google Console',
        ]);
    }

    // Redirect to Google OAuth
    $params = [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
        'response_type' => 'code',
        'access_type' => 'offline',
        'prompt' => 'consent',
    ];

    $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

    return redirect($url);
})->name('auth.google');

Route::get('/auth/google/callback', function (Request $request) {
    $code = $request->get('code');

    if (! $code) {
        return redirect('/admin')->with('error', 'Authorization failed');
    }

    // Exchange code for tokens
    $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
        'client_id' => config('services.google.client_id'),
        'client_secret' => config('services.google.client_secret'),
        'redirect_uri' => config('services.google.redirect_uri'),
        'grant_type' => 'authorization_code',
        'code' => $code,
    ]);

    $tokens = $response->json();

    if (isset($tokens['refresh_token'])) {
        // Save to database for the first project (or you can select which project)
        $project = \App\Models\Project::query()->first();

        if ($project) {
            // Check if credentials exist
            $apiCredential = $project->apiCredentials()
                ->where('service', 'google_search_console')
                ->first();

            $credentials = [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $tokens['refresh_token'],
                'access_token' => $tokens['access_token'] ?? null,
            ];

            if ($apiCredential) {
                // Update existing
                $apiCredential->update([
                    'credentials' => $credentials,
                    'is_active' => true,
                ]);
            } else {
                // Create new
                \App\Models\ApiCredential::query()->create([
                    'project_id' => $project->id,
                    'service' => 'google_search_console',
                    'credentials' => $credentials,
                    'is_active' => true,
                ]);
            }

            return redirect('/admin/api-credentials')->with('success',
                'Google Search Console successfully connected! You can now sync data from Search Console.'
            );
        }

        return redirect('/admin')->with('warning',
            'Credentials obtained but no project found. Refresh token: ' . $tokens['refresh_token']
        );
    }

    return redirect('/admin')->with('error', 'Failed to get refresh token');
})->name('auth.google.callback');
