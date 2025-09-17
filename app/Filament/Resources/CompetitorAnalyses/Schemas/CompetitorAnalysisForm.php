<?php

namespace App\Filament\Resources\CompetitorAnalyses\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompetitorAnalysisForm
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
                TextInput::make('competitor_domain')
                    ->required(),
                TextInput::make('competitor_url')
                    ->url(),
                TextInput::make('position')
                    ->required()
                    ->numeric(),
                TextInput::make('domain_authority')
                    ->numeric(),
                TextInput::make('page_authority')
                    ->numeric(),
                TextInput::make('backlinks_count')
                    ->numeric(),
                TextInput::make('content_length')
                    ->numeric(),
                TextInput::make('keyword_density')
                    ->numeric(),
                Toggle::make('has_schema_markup')
                    ->required(),
                Toggle::make('has_featured_snippet')
                    ->required(),
                TextInput::make('page_speed_score')
                    ->numeric(),
                Toggle::make('is_mobile_friendly')
                    ->required(),
                Toggle::make('has_ssl')
                    ->required(),
                TextInput::make('title_tag'),
                Textarea::make('meta_description')
                    ->columnSpanFull(),
                Textarea::make('headers_structure')
                    ->columnSpanFull(),
                DateTimePicker::make('analyzed_at'),
            ]);
    }
}
