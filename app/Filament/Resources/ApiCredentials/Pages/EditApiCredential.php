<?php

namespace App\Filament\Resources\ApiCredentials\Pages;

use App\Filament\Resources\ApiCredentials\ApiCredentialResource;
use App\Models\ApiCredential;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
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
                ->color('info')
                ->visible(fn () => $this->getRecord() instanceof ApiCredential && $this->getRecord()->service === 'google_ads')
                ->url(function () {
                    $record = $this->getRecord();
                    if (! $record instanceof ApiCredential) {
                        return;
                    }
                    $credentials = $record->credentials;
                    $clientId = $credentials['client_id'] ?? null;
                    $clientSecret = $credentials['client_secret'] ?? null;

                    if (! $clientId || ! $clientSecret) {
                        return;
                    }

                    return app('url')->route('google-ads.oauth.start', [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                    ]);
                })
                ->openUrlInNewTab(),
            DeleteAction::make()
                ->before(function ($record) {
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

        return $data;
    }
}
