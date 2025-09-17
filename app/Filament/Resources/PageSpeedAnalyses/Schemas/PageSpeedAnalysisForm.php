<?php

namespace App\Filament\Resources\PageSpeedAnalyses\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PageSpeedAnalysisForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->required(),
                Select::make('keyword_id')
                    ->relationship('keyword', 'id'),
                TextInput::make('tested_url')
                    ->url()
                    ->required(),
                TextInput::make('device_type')
                    ->required()
                    ->default('desktop'),
                TextInput::make('lcp')
                    ->numeric(),
                TextInput::make('fid')
                    ->numeric(),
                TextInput::make('cls')
                    ->numeric(),
                TextInput::make('fcp')
                    ->numeric(),
                TextInput::make('inp')
                    ->numeric(),
                TextInput::make('ttfb')
                    ->numeric(),
                TextInput::make('performance_score')
                    ->numeric(),
                TextInput::make('accessibility_score')
                    ->numeric(),
                TextInput::make('best_practices_score')
                    ->numeric(),
                TextInput::make('seo_score')
                    ->numeric(),
                TextInput::make('total_page_size')
                    ->numeric(),
                TextInput::make('total_requests')
                    ->numeric(),
                TextInput::make('load_time')
                    ->numeric(),
                Textarea::make('resource_breakdown')
                    ->columnSpanFull(),
                Textarea::make('third_party_resources')
                    ->columnSpanFull(),
                Textarea::make('opportunities')
                    ->columnSpanFull(),
                Textarea::make('diagnostics')
                    ->columnSpanFull(),
                TextInput::make('images_count')
                    ->numeric(),
                TextInput::make('unoptimized_images')
                    ->numeric(),
                TextInput::make('images_without_alt')
                    ->numeric(),
                TextInput::make('render_blocking_resources')
                    ->numeric(),
                TextInput::make('unused_css_bytes')
                    ->numeric(),
                TextInput::make('unused_js_bytes')
                    ->numeric(),
                TextInput::make('analysis_source')
                    ->required()
                    ->default('pagespeed'),
                DateTimePicker::make('analyzed_at')
                    ->required(),
                Textarea::make('raw_response')
                    ->columnSpanFull(),
            ]);
    }
}
