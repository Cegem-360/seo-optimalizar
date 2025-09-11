<?php

namespace App\Filament\Resources\Keywords\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KeywordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Keyword Information')
                    ->components([
                        Grid::make(2)
                            ->components([
                                TextInput::make('keyword')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter keyword phrase'),

                                TextInput::make('category')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Brand, Product, Service'),
                            ]),

                        Grid::make(3)
                            ->components([
                                Select::make('priority')
                                    ->options([
                                        'high' => 'High',
                                        'medium' => 'Medium',
                                        'low' => 'Low',
                                    ])
                                    ->default('medium')
                                    ->required(),

                                Select::make('intent_type')
                                    ->options([
                                        'informational' => 'Informational',
                                        'navigational' => 'Navigational',
                                        'commercial' => 'Commercial',
                                        'transactional' => 'Transactional',
                                    ])
                                    ->placeholder('Select intent type'),

                                TextInput::make('geo_target')
                                    ->default('global')
                                    ->placeholder('e.g., US, UK, global'),
                            ]),

                        Grid::make(3)
                            ->components([
                                Select::make('language')
                                    ->options([
                                        'hu' => 'Magyar',
                                        'en' => 'English',
                                        'de' => 'Deutsch',
                                        'fr' => 'Français',
                                        'es' => 'Español',
                                    ])
                                    ->default('hu')
                                    ->required(),

                                TextInput::make('search_volume')
                                    ->numeric()
                                    ->placeholder('Monthly search volume'),

                                TextInput::make('difficulty_score')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->placeholder('Keyword difficulty (1-100)'),

                                TextInput::make('competition_index')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->placeholder('Competition index (0-100)')
                                    ->helperText('Detailed competition score from Google Ads'),
                            ]),

                        Grid::make(2)
                            ->components([
                                TextInput::make('low_top_of_page_bid')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('Low bid estimate'),

                                TextInput::make('high_top_of_page_bid')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('High bid estimate'),
                            ]),

                        Textarea::make('notes')
                            ->rows(3)
                            ->placeholder('Optional notes about this keyword'),
                    ]),
            ]);
    }
}
