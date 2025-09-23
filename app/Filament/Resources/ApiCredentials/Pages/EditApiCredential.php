<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApiCredentials\Pages;

use App\Filament\Resources\ApiCredentials\ApiCredentialResource;
use App\Models\ApiCredential;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class EditApiCredential extends EditRecord
{
    protected static string $resource = ApiCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('google_ads_oauth')
                ->label('Generate Google Ads Refresh Token')
                ->icon('heroicon-o-key')
                ->color('success')
                ->visible(function (): bool {
                    $model = $this->getRecord();
                    if (! $model instanceof ApiCredential || $model->service !== 'google_ads') {
                        return false;
                    }

                    $credentials = $model->credentials ?? [];
                    $clientId = $credentials['client_id'] ?? null;
                    $clientSecret = $credentials['client_secret'] ?? null;

                    return ! empty($clientId) && ! empty($clientSecret);
                })
                ->disabled(function (): bool {
                    $model = $this->getRecord();
                    if (! $model instanceof ApiCredential) {
                        return true;
                    }

                    $credentials = $model->credentials ?? [];
                    $clientId = $credentials['client_id'] ?? null;
                    $clientSecret = $credentials['client_secret'] ?? null;

                    return empty($clientId) || empty($clientSecret);
                })
                ->tooltip(function (): ?string {
                    $model = $this->getRecord();
                    if (! $model instanceof ApiCredential) {
                        return null;
                    }

                    $credentials = $model->credentials ?? [];
                    $clientId = $credentials['client_id'] ?? null;
                    $clientSecret = $credentials['client_secret'] ?? null;

                    if (empty($clientId) || empty($clientSecret)) {
                        return 'Először add meg a client_id és client_secret mezőket, majd mentsd el!';
                    }

                    return 'Kattints ide a Google Ads OAuth folyamat indításához';
                })
                ->url(function () {
                    $model = $this->getRecord();
                    if (! $model instanceof ApiCredential) {
                        return;
                    }

                    $credentials = $model->credentials;
                    $clientId = $credentials['client_id'] ?? null;
                    $clientSecret = $credentials['client_secret'] ?? null;

                    if (! $clientId || ! $clientSecret) {
                        return;
                    }

                    return route('google-ads.oauth.start', [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                    ]);
                })
                ->openUrlInNewTab(),
            DeleteAction::make()
                ->before(function ($record): void {
                    // Delete the service account file when deleting the record
                    if ($record instanceof ApiCredential) {
                        $record->deleteServiceAccountFile();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Extract property_url and property_id from credentials for display
        if (isset($data['credentials'])) {
            if (isset($data['credentials']['property_url'])) {
                $data['property_url'] = $data['credentials']['property_url'];
            }

            if (isset($data['credentials']['property_id'])) {
                $data['property_id'] = $data['credentials']['property_id'];
            }
        }

        // Check for Google Ads refresh token in session (after OAuth)
        if ($data['service'] === 'google_ads') {
            $sessionRefreshToken = Session::get('google_ads_refresh_token');

            Log::debug('Google Ads OAuth token check in mutateFormDataBeforeFill', [
                'service' => $data['service'],
                'session_token' => $sessionRefreshToken ? 'EXISTS (' . substr((string) $sessionRefreshToken, 0, 10) . '...)' : 'NOT_FOUND',
                'credentials_before' => isset($data['credentials']) ? array_keys($data['credentials']) : 'NOT_SET',
            ]);

            if ($sessionRefreshToken) {
                // Update credentials with the new refresh token
                if (! isset($data['credentials'])) {
                    $data['credentials'] = [];
                }

                $data['credentials']['refresh_token'] = $sessionRefreshToken;

                // Automatically save the refresh token to database
                $record = $this->getRecord();
                if ($record instanceof ApiCredential) {
                    $currentCredentials = $record->credentials ?? [];
                    $currentCredentials['refresh_token'] = $sessionRefreshToken;

                    $record->update(['credentials' => $currentCredentials]);
                }

                // Clear the session token to prevent reuse
                Session::forget('google_ads_refresh_token');

                // Show success notification
                Notification::make()
                    ->title('Google Ads OAuth Successful')
                    ->body('Refresh token has been automatically added and saved to your credentials.')
                    ->success()
                    ->persistent()
                    ->send();
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle service account file upload
        if (isset($data['service_account_json_upload']) && $data['service_account_json_upload']) {
            $tempPath = Storage::disk('local')->path($data['service_account_json_upload']);

            if (file_exists($tempPath)) {
                $content = file_get_contents($tempPath);
                $jsonData = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Delete old file if exists
                    $record = $this->getRecord();
                    if ($record instanceof ApiCredential) {
                        $record->deleteServiceAccountFile();
                    }

                    // Store service account JSON in credentials
                    $data['credentials']['service_account_json'] = $jsonData;

                    // Store the new file
                    $filename = 'project_' . $data['project_id'] . '_' . $data['service'] . '_' . time() . '.json';
                    $directory = storage_path('app/service-accounts');

                    if (! is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    file_put_contents($directory . '/' . $filename, $content);
                    $data['service_account_file'] = $filename;

                    // Clean up temp file
                    Storage::disk('local')->delete($data['service_account_json_upload']);
                }
            }

            unset($data['service_account_json_upload']);
        }

        // Handle property_url for Google Search Console
        if (isset($data['property_url']) && $data['service'] === 'google_search_console') {
            $data['credentials']['property_url'] = $data['property_url'];
            unset($data['property_url']);
        }

        // Handle property_id for GA4
        if (isset($data['property_id']) && $data['service'] === 'google_analytics_4') {
            $data['credentials']['property_id'] = $data['property_id'];
            unset($data['property_id']);
        }

        // Handle Google Ads refresh token from session (backup check during save)
        if ($data['service'] === 'google_ads') {
            $sessionRefreshToken = Session::get('google_ads_refresh_token');

            Log::debug('Google Ads OAuth token check in mutateFormDataBeforeSave', [
                'service' => $data['service'],
                'session_token' => $sessionRefreshToken ? 'EXISTS (' . substr((string) $sessionRefreshToken, 0, 10) . '...)' : 'NOT_FOUND',
                'credentials_before' => isset($data['credentials']) ? array_keys($data['credentials']) : 'NOT_SET',
            ]);

            if ($sessionRefreshToken) {
                $data['credentials']['refresh_token'] = $sessionRefreshToken;
                Session::forget('google_ads_refresh_token');
            }
        }

        return $data;
    }
}
