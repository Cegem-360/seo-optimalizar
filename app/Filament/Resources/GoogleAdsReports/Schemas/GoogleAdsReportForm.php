<?php

namespace App\Filament\Resources\GoogleAdsReports\Schemas;

use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GoogleAdsReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Report Information')
                    ->schema([
                        Select::make('project_id')
                            ->label('Project')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable(),

                        DatePicker::make('report_date')
                            ->label('Report Date')
                            ->required()
                            ->default(now()),
                    ])
                    ->columns(2),

                Section::make('Metadata')
                    ->schema([
                        KeyValue::make('metadata')
                            ->label('Report Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->addButtonLabel('Add metadata')
                            ->columnSpan('full'),
                    ]),

                Section::make('Statistics')
                    ->schema([
                        KeyValue::make('statistics')
                            ->label('Report Statistics')
                            ->keyLabel('Metric')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->addButtonLabel('Add statistic')
                            ->columnSpan('full'),
                    ]),

                Section::make('Raw Data')
                    ->schema([
                        Textarea::make('keyword_data')
                            ->label('Keyword Data (JSON)')
                            ->rows(10)
                            ->columnSpan('full')
                            ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn($state) => json_decode($state, true)),

                        Textarea::make('historical_metrics')
                            ->label('Historical Metrics (JSON)')
                            ->rows(10)
                            ->columnSpan('full')
                            ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn($state) => json_decode($state, true)),

                        Textarea::make('bulk_results')
                            ->label('Bulk Results (JSON)')
                            ->rows(10)
                            ->columnSpan('full')
                            ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn($state) => json_decode($state, true)),

                        Textarea::make('raw_data')
                            ->label('Complete Raw Data (JSON)')
                            ->rows(15)
                            ->columnSpan('full')
                            ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn($state) => json_decode($state, true)),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
