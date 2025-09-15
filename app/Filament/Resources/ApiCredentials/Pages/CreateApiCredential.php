<?php

namespace App\Filament\Resources\ApiCredentials\Pages;

use App\Filament\Resources\ApiCredentials\ApiCredentialResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Filesystem\FilesystemManager;

class CreateApiCredential extends CreateRecord
{
    protected static string $resource = ApiCredentialResource::class;

    public function __construct(private readonly FilesystemManager $filesystemManager) {}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle service account file upload
        if (isset($data['service_account_json_upload'])) {
            $tempPath = $this->filesystemManager->disk('local')->path($data['service_account_json_upload']);

            if (file_exists($tempPath)) {
                $content = file_get_contents($tempPath);
                $jsonData = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Store service account JSON in credentials
                    $data['credentials']['service_account_json'] = $jsonData;

                    // Store the file
                    $filename = 'project_' . $data['project_id'] . '_' . $data['service'] . '_' . time() . '.json';
                    $directory = storage_path('app/service-accounts');

                    if (! is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    file_put_contents($directory . '/' . $filename, $content);
                    $data['service_account_file'] = $filename;

                    // Clean up temp file
                    $this->filesystemManager->disk('local')->delete($data['service_account_json_upload']);
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
