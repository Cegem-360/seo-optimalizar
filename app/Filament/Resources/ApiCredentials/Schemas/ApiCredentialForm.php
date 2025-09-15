<?php

namespace App\Filament\Resources\ApiCredentials\Schemas;

use App\Models\ApiCredential;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ApiCredentialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('API Service Configuration')
                    ->columnSpanFull()
                    ->description('Configure API credentials for external services')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Hidden::make('project_id')
                                    ->default(fn () => Filament::getTenant() instanceof Project ? Filament::getTenant()->id : null)
                                    ->required(),

                                Select::make('service')
                                    ->label('API Service')
                                    ->options([
                                        'google_search_console' => 'Google Search Console',
                                        'google_analytics' => 'Google Analytics (Universal)',
                                        'google_analytics_4' => 'Google Analytics 4',
                                        'google_pagespeed_insights' => 'PageSpeed Insights',
                                        'google_ads' => 'Google Ads (Keyword Planner)',
                                        'gemini' => 'Google Gemini AI',
                                        'mobile_friendly_test' => 'Mobile-Friendly Test',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($set) => $set('credentials', [])),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Enable or disable this API integration'),
                            ]),

                        FileUpload::make('service_account_json_upload')
                            ->label('Service Account JSON File')
                            ->acceptedFileTypes(['application/json', 'text/json', 'text/plain'])
                            ->disk('local')
                            ->directory('temp-service-accounts')
                            ->helperText('Upload the service account JSON file from Google Cloud Console')
                            ->required()
                            ->columnSpanFull()
                            ->visible(fn ($get): bool => in_array($get('service'), [
                                'google_search_console',
                                'google_analytics_4',
                            ]))
                            ->afterStateUpdated(function ($state, $record, $set): void {
                                if ($state) {
                                    $tempPath = Storage::disk('local')->path($state);
                                    if (file_exists($tempPath)) {
                                        $content = file_get_contents($tempPath);

                                        // Validate JSON
                                        $jsonData = json_decode($content, true);
                                        if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['type']) && $jsonData['type'] === 'service_account' && $record instanceof ApiCredential) {
                                            // Store the file
                                            $filename = $record->storeServiceAccountFile($content);
                                            $record->update(['service_account_file' => $filename]);
                                            // Clean up temp file
                                            Storage::disk('local')->delete($state);
                                            // Update credentials with parsed data
                                            $credentials = $record->credentials ?? [];
                                            $credentials['service_account_json'] = $jsonData;
                                            $set('credentials', $credentials);
                                        }
                                    }
                                }
                            }),

                        TextInput::make('property_url')
                            ->label('Property URL')
                            ->placeholder('https://example.com or sc-domain:example.com')
                            ->required()
                            ->visible(fn ($get): bool => $get('service') === 'google_search_console')
                            ->afterStateUpdated(function ($state, $get, $set): void {
                                if ($state) {
                                    $credentials = $get('credentials') ?? [];
                                    $credentials['property_url'] = $state;
                                    $set('credentials', $credentials);
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('property_id')
                            ->label('GA4 Property ID')
                            ->placeholder('123456789')
                            ->required()
                            ->visible(fn ($get): bool => $get('service') === 'google_analytics_4')
                            ->afterStateUpdated(function ($state, $get, $set): void {
                                if ($state) {
                                    $credentials = $get('credentials') ?? [];
                                    $credentials['property_id'] = $state;
                                    $set('credentials', $credentials);
                                }
                            })
                            ->columnSpanFull(),

                        // Google Ads OAuth helper section
                        Placeholder::make('google_ads_oauth_helper')
                            ->label('OAuth2 Setup Required')
                            ->content(fn (): HtmlString => new HtmlString('
                                <div class="p-4 border rounded-lg bg-blue-50 border-blue-200">
                                    <h3 class="font-semibold text-blue-900 mb-2">🔐 Google Ads OAuth2 Authentication</h3>
                                    <p class="text-sm text-blue-800 mb-3">Google Ads requires OAuth2 authentication. Follow these steps:</p>
                                    
                                    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                                        <h4 class="font-semibold text-yellow-900 text-sm mb-2">⚠️ Development Setup Required</h4>
                                        <p class="text-xs text-yellow-800 mb-2">Google OAuth doesn\'t accept .test domains. Choose one option:</p>
                                        
                                        <div class="space-y-2 text-xs">
                                            <div>
                                                <strong>Option 1 (Recommended):</strong> Use ngrok
                                                <div class="bg-yellow-100 p-2 rounded mt-1 font-mono">
                                                    ngrok http https://seo-optimalizar.test
                                                </div>
                                                <p class="text-yellow-700 mt-1">Then use the ngrok HTTPS URL as redirect URI</p>
                                            </div>
                                            
                                            <div>
                                                <strong>Option 2:</strong> Use localhost
                                                <div class="bg-yellow-100 p-2 rounded mt-1 font-mono">
                                                    php artisan serve --port=8000
                                                </div>
                                                <p class="text-yellow-700 mt-1">Then use: <code>http://localhost:8000/admin/google-ads/oauth/callback</code></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <ol class="text-sm text-blue-800 list-decimal list-inside space-y-1 mb-3">
                                        <li>Create OAuth2 Client in <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline">Google Cloud Console</a></li>
                                        <li>Add redirect URI from one of the options above</li>
                                        <li>Run the command below to generate refresh token</li>
                                        <li>Add the required credentials below</li>
                                    </ol>
                                    <div class="bg-blue-100 p-2 rounded font-mono text-xs mb-3">
                                        php artisan google-ads:generate-refresh-token
                                    </div>
                                    <p class="text-xs text-blue-700">💡 The command will guide you through the OAuth process and generate the refresh_token.</p>
                                </div>
                            '))
                            ->columnSpanFull()
                            ->visible(fn ($get): bool => $get('service') === 'google_ads'),

                        KeyValue::make('credentials')
                            ->label('API Credentials')
                            ->keyLabel('Credential Key')
                            ->valueLabel('Credential Value')
                            ->helperText(function ($get): string {
                                $service = $get('service');

                                return match ($service) {
                                    'google_ads' => 'Click "Generate Refresh Token" button above to authenticate with Google Ads. Required: developer_token (from Google Ads API Center), customer_id (10 digits without dashes)',
                                    'gemini' => 'Enter: api_key (Get from Google AI Studio)',
                                    'google_analytics' => 'Enter: client_id, client_secret, refresh_token, property_id',
                                    'google_pagespeed_insights' => 'Enter: api_key (Enable PageSpeed Insights API in Google Cloud Console)',
                                    default => 'Enter the API credentials as key-value pairs.'
                                };
                            })
                            ->addActionLabel('Add Credential')
                            ->reorderable(false)
                            ->required()
                            ->columnSpanFull()
                            ->visible(fn ($get): bool => ! in_array($get('service'), [
                                'google_search_console',
                                'google_analytics_4',
                            ])),

                        Hidden::make('credentials')
                            ->default([])
                            ->visible(fn ($get): bool => in_array($get('service'), [
                                'google_search_console',
                                'google_analytics_4',
                            ])),
                    ]),
            ]);
    }
}
