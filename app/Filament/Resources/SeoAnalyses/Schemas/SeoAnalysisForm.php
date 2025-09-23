<?php

declare(strict_types=1);

namespace App\Filament\Resources\SeoAnalyses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SeoAnalysisForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('keyword_id')
                    ->relationship('keyword', 'id')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required(),
                TextInput::make('competition_level'),
                TextInput::make('search_intent'),
                Textarea::make('dominant_content_types')
                    ->columnSpanFull(),
                Textarea::make('opportunities')
                    ->columnSpanFull(),
                Textarea::make('challenges')
                    ->columnSpanFull(),
                Textarea::make('optimization_tips')
                    ->columnSpanFull(),
                Textarea::make('summary')
                    ->columnSpanFull(),
                TextInput::make('position_rating'),
                TextInput::make('current_position')
                    ->numeric(),
                TextInput::make('target_position')
                    ->numeric(),
                TextInput::make('estimated_timeframe'),
                Textarea::make('main_competitors')
                    ->columnSpanFull(),
                Textarea::make('competitor_advantages')
                    ->columnSpanFull(),
                Textarea::make('improvement_areas')
                    ->columnSpanFull(),
                Textarea::make('quick_wins')
                    ->columnSpanFull(),
                Textarea::make('detailed_analysis')
                    ->columnSpanFull(),
                Textarea::make('raw_response')
                    ->columnSpanFull(),
                TextInput::make('analysis_source')
                    ->required()
                    ->default('gemini'),
            ]);
    }
}
