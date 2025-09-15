<?php

namespace App\Filament\Resources\WebsiteAnalyses\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WebsiteAnalysisForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Elemzés beállítások')
                    ->description('Weboldal elemzés alapadatai')
                    ->schema([
                        Select::make('project_id')
                            ->label('Projekt')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable(),

                        TextInput::make('url')
                            ->label('URL')
                            ->required()
                            ->url()
                            ->placeholder('https://example.com'),

                        Select::make('analysis_type')
                            ->label('Elemzés típusa')
                            ->options([
                                'seo' => 'SEO elemzés',
                                'ux' => 'UX elemzés',
                                'content' => 'Tartalom elemzés',
                                'technical' => 'Technikai elemzés',
                                'competitor' => 'Versenytárs elemzés',
                            ])
                            ->required(),

                        Select::make('ai_provider')
                            ->label('AI szolgáltató')
                            ->options([
                                'openai' => 'OpenAI',
                                'claude' => 'Claude',
                                'gemini' => 'Google Gemini',
                                'custom' => 'Egyéb',
                            ])
                            ->required(),

                        TextInput::make('ai_model')
                            ->label('AI modell')
                            ->placeholder('pl. gpt-4, claude-3-opus')
                            ->helperText('Opcionális: specifikus modell megadása'),

                        Select::make('status')
                            ->label('Státusz')
                            ->options([
                                'pending' => 'Várakozik',
                                'processing' => 'Feldolgozás alatt',
                                'completed' => 'Kész',
                                'failed' => 'Sikertelen',
                            ])
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation === 'create')
                            ->default('pending'),
                    ])
                    ->columns(2),

                Section::make('Eredmények')
                    ->description('Elemzés eredményei')
                    ->schema([
                        TextInput::make('overall_score')
                            ->label('Összpontszám')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/100'),

                        DateTimePicker::make('analyzed_at')
                            ->label('Elemzés időpontja')
                            ->disabled(),

                        KeyValue::make('scores')
                            ->label('Részpontszámok')
                            ->keyLabel('Terület')
                            ->valueLabel('Pontszám')
                            ->disabled(),

                        KeyValue::make('metadata')
                            ->label('Metaadatok')
                            ->keyLabel('Kulcs')
                            ->valueLabel('Érték')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsed(fn (string $operation): bool => $operation === 'create'),

                Section::make('AI válasz')
                    ->schema([
                        Textarea::make('raw_response')
                            ->label('Nyers válasz')
                            ->rows(10)
                            ->disabled()
                            ->columnSpanFull(),

                        Textarea::make('error_message')
                            ->label('Hibaüzenet')
                            ->disabled()
                            ->visible(fn ($get): bool => $get('status') === 'failed')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(fn (string $operation): bool => $operation === 'create'),
            ]);
    }
}
