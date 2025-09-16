<?php

namespace App\Filament\Resources\ApiCredentials\Schemas;

use App\Models\ApiCredential;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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

                        TextInput::make('current_service_account')
                            ->label('Current Service Account File')
                            ->disabled()
                            ->visible(fn ($get): bool => in_array($get('service'), [
                                'google_search_console',
                                'google_analytics_4',
                            ]))
                            ->columnSpanFull()
                            ->helperText('This shows the currently uploaded service account file'),

                        FileUpload::make('service_account_file')
                            ->label('Service Account JSON File')
                            ->acceptedFileTypes(['application/json', 'text/json', 'text/plain'])
                            ->disk('local')
                            ->directory('service-accounts')
                            ->helperText('Upload the service account JSON file from Google Cloud Console')
                            ->required(function (ApiCredential $record): bool {
                                return ! $record->service_account_file ||
                                       ! Storage::disk('local')->exists('service-accounts/' . $record->service_account_file);
                            })
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => in_array($get('service'), [
                                'google_search_console',
                                'google_analytics_4',
                            ])),
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
                        TextEntry::make('google_ads_oauth_helper')
                            ->label('OAuth2 Setup Required')
                            ->state(fn (): HtmlString => new HtmlString('
                                <div class="p-4 border rounded-lg bg-blue-50 border-blue-200">
                                    <h3 class="font-semibold text-blue-900 mb-2">🔐 Google Ads OAuth2 Authentication</h3>
                                    <p class="text-sm text-blue-800 mb-3">Google Ads requires OAuth2 authentication. Follow these steps for production setup:</p>

                                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded">
                                        <h4 class="font-semibold text-green-900 text-sm mb-2">✅ Production Domain Setup</h4>
                                        <p class="text-xs text-green-800 mb-2">For production domains (like .com, .hu, etc.), use your actual domain:</p>

                                        <div class="space-y-2 text-xs">
                                            <div class="bg-green-100 p-2 rounded font-mono">
                                                https://yourdomain.com/admin/google-ads/oauth/callback
                                            </div>
                                            <p class="text-green-700">Replace "yourdomain.com" with your actual domain name</p>
                                        </div>
                                    </div>

                                    <ol class="text-sm text-blue-800 list-decimal list-inside space-y-2 mb-4">
                                        <li><strong>Google Cloud Console beállítása:</strong>
                                            <ul class="list-disc list-inside ml-4 mt-1 text-xs space-y-1">
                                                <li>Lépj be a <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="underline text-blue-600">Google Cloud Console</a>-ba</li>
                                                <li>Válassz ki vagy hozz létre egy projektet</li>
                                                <li>Engedélyezd a Google Ads API-t a "APIs & Services > Library" menüben</li>
                                            </ul>
                                        </li>
                                        <li><strong>OAuth2 Client létrehozása:</strong>
                                            <ul class="list-disc list-inside ml-4 mt-1 text-xs space-y-1">
                                                <li>Menj a "Credentials" fülre</li>
                                                <li>Kattints "Create Credentials" > "OAuth client ID"</li>
                                                <li>Válaszd a "Web application" típust</li>
                                                <li>Add hozzá a redirect URI-t: <code class="bg-gray-100 px-1 rounded">https://yourdomain.com/admin/google-ads/oauth/callback</code></li>
                                            </ul>
                                        </li>
                                        <li><strong>Refresh token generálása:</strong>
                                            <ul class="list-disc list-inside ml-4 mt-1 text-xs space-y-1">
                                                <li>Add meg a Client ID és Client Secret mezőket alább</li>
                                                <li>Mentsd el a credential-öket</li>
                                                <li>Kattints a "Generate Google Ads Refresh Token" gombra a felső menüben</li>
                                            </ul>
                                        </li>
                                        <li><strong>Véglegesítés:</strong>
                                            <ul class="list-disc list-inside ml-4 mt-1 text-xs space-y-1">
                                                <li>Az OAuth folyamat után kapott refresh_token-t add meg alább</li>
                                                <li>Add meg a Google Ads Customer ID-t (10 jegyű szám kötőjelek nélkül)</li>
                                            </ul>
                                        </li>
                                    </ol>

                                    <div class="bg-amber-50 border border-amber-200 rounded p-3 text-xs">
                                        <strong class="text-amber-800">💡 Fejlesztési környezethez:</strong>
                                        <p class="text-amber-700 mt-1">Ha .test domainen fejlesztesz, használd az ngrok-ot: <code class="bg-amber-100 px-1 rounded">ngrok http https://seo-optimalizar.test</code></p>
                                    </div>
                                </div>
                            '))
                            ->columnSpanFull()
                            ->visible(fn ($get): bool => $get('service') === 'google_ads'),

                        KeyValue::make('credentials')
                            ->label('API Credentials')
                            ->keyLabel('Credential Key')
                            ->valueLabel('Credential Value')
                            ->helperText(function ($get): HtmlString {
                                $service = $get('service');

                                return new HtmlString(match ($service) {
                                    'google_ads' => '
                                        <div class="space-y-2 text-sm">
                                            <p><strong>Szükséges mezők:</strong></p>
                                            <ul class="list-disc ml-4 space-y-1">
                                                <li><code class="bg-gray-100 px-1 rounded">client_id</code> - OAuth2 Client ID (Google Cloud Console)</li>
                                                <li><code class="bg-gray-100 px-1 rounded">client_secret</code> - OAuth2 Client Secret (Google Cloud Console)</li>
                                                <li><code class="bg-gray-100 px-1 rounded">developer_token</code> - Google Ads API fejlesztői token (<a href="https://developers.google.com/google-ads/api/docs/first-call/dev-token" target="_blank" class="text-blue-600 underline">kérelmezd itt</a>)</li>
                                                <li><code class="bg-gray-100 px-1 rounded">customer_id</code> - Google Ads ügyfél ID (10 jegyű szám kötőjelek nélkül, pl: 1234567890)</li>
                                                <li><code class="bg-gray-100 px-1 rounded">refresh_token</code> - OAuth refresh token (generálódik automatikusan)</li>
                                            </ul>
                                            <p class="text-blue-600"><strong>💡 Tipp:</strong> Mentsd el először a client_id és client_secret mezőket, majd használd a felső "Generate Google Ads Refresh Token" gombot!</p>
                                        </div>
                                    ',
                                    'gemini' => '
                                        <div class="space-y-2 text-sm">
                                            <p><strong>Szükséges mezők:</strong></p>
                                            <ul class="list-disc ml-4">
                                                <li><code class="bg-gray-100 px-1 rounded">api_key</code> - API kulcs (<a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-600 underline">szerezd be itt</a>)</li>
                                            </ul>
                                        </div>
                                    ',
                                    'google_analytics' => '
                                        <div class="space-y-2 text-sm">
                                            <p><strong>Szükséges mezők:</strong></p>
                                            <ul class="list-disc ml-4 space-y-1">
                                                <li><code class="bg-gray-100 px-1 rounded">client_id</code> - OAuth2 Client ID</li>
                                                <li><code class="bg-gray-100 px-1 rounded">client_secret</code> - OAuth2 Client Secret</li>
                                                <li><code class="bg-gray-100 px-1 rounded">refresh_token</code> - OAuth refresh token</li>
                                                <li><code class="bg-gray-100 px-1 rounded">property_id</code> - Google Analytics property ID</li>
                                            </ul>
                                        </div>
                                    ',
                                    'google_pagespeed_insights' => '
                                        <div class="space-y-2 text-sm">
                                            <p><strong>Szükséges mezők:</strong></p>
                                            <ul class="list-disc ml-4">
                                                <li><code class="bg-gray-100 px-1 rounded">api_key</code> - API kulcs (Google Cloud Console-ban engedélyezd a PageSpeed Insights API-t)</li>
                                            </ul>
                                        </div>
                                    ',
                                    default => 'Add meg az API credentialeket kulcs-érték párok formájában.'
                                });
                            })
                            ->default(function ($get): array {
                                $service = $get('service');

                                return match ($service) {
                                    'google_ads' => [
                                        'client_id' => '',
                                        'client_secret' => '',
                                        'developer_token' => '',
                                        'customer_id' => '',
                                        'refresh_token' => '',
                                    ],
                                    'gemini' => [
                                        'api_key' => '',
                                    ],
                                    'google_analytics' => [
                                        'client_id' => '',
                                        'client_secret' => '',
                                        'refresh_token' => '',
                                        'property_id' => '',
                                    ],
                                    'google_pagespeed_insights' => [
                                        'api_key' => '',
                                    ],
                                    default => []
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
