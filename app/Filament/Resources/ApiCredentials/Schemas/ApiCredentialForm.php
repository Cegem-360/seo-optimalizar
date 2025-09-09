<?php

namespace App\Filament\Resources\ApiCredentials\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                                    ->default(fn () => Filament::getTenant() instanceof \App\Models\Project ? Filament::getTenant()->id : null)
                                    ->required(),

                                Select::make('service')
                                    ->label('API Service')
                                    ->options([
                                        'google_search_console' => 'Google Search Console',
                                        'google_analytics' => 'Google Analytics 4',
                                        'google_pagespeed_insights' => 'PageSpeed Insights',
                                        'google_ads' => 'Google Ads (Keyword Planner)',
                                        'gemini' => 'Google Gemini AI',
                                        'mobile_friendly_test' => 'Mobile-Friendly Test',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('credentials', [])),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Enable or disable this API integration'),
                            ]),

                        KeyValue::make('credentials')
                            ->label('API Credentials')
                            ->keyLabel('Credential Key')
                            ->valueLabel('Credential Value')
                            ->helperText(function (callable $get): string {
                                $service = $get('service');

                                return match ($service) {
                                    'google_ads' => 'Google Ads: client_id, client_secret, refresh_token, developer_token, customer_id',
                                    'gemini' => 'Google Gemini: api_key',
                                    'google_search_console' => 'Google Search Console: credentials file content or service account JSON',
                                    'google_analytics' => 'Google Analytics: credentials file content or service account JSON',
                                    'google_pagespeed_insights' => 'PageSpeed Insights: api_key',
                                    default => 'Enter the API credentials as key-value pairs. These will be encrypted automatically.'
                                };
                            })
                            ->addActionLabel('Add Credential')
                            ->reorderable(false)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
