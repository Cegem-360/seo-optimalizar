<?php

namespace App\Filament\Resources\SerpAnalysisResults\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SerpAnalysisResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Alapadatok')
                    ->schema([
                        Select::make('project_id')
                            ->label('Projekt')
                            ->relationship('project', 'name')
                            ->required()
                            ->disabled(),

                        Select::make('keyword_id')
                            ->label('Kulcsszó')
                            ->relationship('keyword', 'keyword')
                            ->required()
                            ->disabled(),

                        TextInput::make('search_id')
                            ->label('Keresés ID')
                            ->disabled(),
                    ])
                    ->columns(3),

                Section::make('AI Elemzés')
                    ->schema([
                        Textarea::make('ai_analysis')
                            ->label('Részletes elemzés')
                            ->rows(8)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
