<?php

namespace App\Filament\Resources\Rankings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RankingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ranking Information')
                    ->components([
                        Select::make('keyword_id')
                            ->relationship('keyword', 'keyword')
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('keyword')
                                    ->required(),
                            ]),

                        Grid::make(3)
                            ->components([
                                TextInput::make('position')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(200)
                                    ->placeholder('Current position'),

                                TextInput::make('previous_position')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(200)
                                    ->placeholder('Previous position'),

                                Toggle::make('featured_snippet')
                                    ->label('Featured Snippet'),
                            ]),

                        Grid::make(2)
                            ->components([
                                TextInput::make('url')
                                    ->url()
                                    ->placeholder('Ranking URL'),

                                DateTimePicker::make('checked_at')
                                    ->required()
                                    ->default(now()),
                            ]),
                    ]),
            ]);
    }
}
