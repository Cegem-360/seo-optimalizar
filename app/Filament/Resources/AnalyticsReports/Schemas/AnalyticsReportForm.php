<?php

namespace App\Filament\Resources\AnalyticsReports\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Schemas\Schema;

class AnalyticsReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('project_id')
                                    ->label('Project')
                                    ->relationship('project', 'name')
                                    ->required()
                                    ->searchable(),

                                DatePicker::make('report_date')
                                    ->label('Report Date')
                                    ->required()
                                    ->default(now()->yesterday()),
                            ]),
                    ]),

                Section::make('Overview Metrics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('sessions')
                                    ->label('Sessions')
                                    ->required()
                                    ->numeric()
                                    ->default(0),

                                TextInput::make('active_users')
                                    ->label('Active Users')
                                    ->required()
                                    ->numeric()
                                    ->default(0),

                                TextInput::make('total_users')
                                    ->label('Total Users')
                                    ->required()
                                    ->numeric()
                                    ->default(0),

                                TextInput::make('new_users')
                                    ->label('New Users')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('bounce_rate')
                                    ->label('Bounce Rate (%)')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%'),

                                TextInput::make('average_session_duration')
                                    ->label('Avg Session Duration (s)')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('s'),

                                TextInput::make('screen_page_views')
                                    ->label('Page Views')
                                    ->required()
                                    ->numeric()
                                    ->default(0),

                                TextInput::make('conversions')
                                    ->label('Conversions')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ]),

                Section::make('Detailed Analytics Data')
                    ->description('JSON data for detailed analytics breakdown')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('traffic_sources')
                                    ->label('Traffic Sources')
                                    ->helperText('JSON array of traffic source data')
                                    ->rows(3),

                                Textarea::make('top_pages')
                                    ->label('Top Pages')
                                    ->helperText('JSON array of top performing pages')
                                    ->rows(3),

                                Textarea::make('user_demographics')
                                    ->label('User Demographics')
                                    ->helperText('JSON array of user location and language data')
                                    ->rows(3),

                                Textarea::make('device_data')
                                    ->label('Device Data')
                                    ->helperText('JSON array of device and browser data')
                                    ->rows(3),

                                Textarea::make('conversion_data')
                                    ->label('Conversion Data')
                                    ->helperText('JSON array of conversion events')
                                    ->rows(3),

                                Textarea::make('real_time')
                                    ->label('Real-time Data')
                                    ->helperText('JSON array of real-time analytics')
                                    ->rows(3),
                            ]),

                        Textarea::make('raw_data')
                            ->label('Raw Data')
                            ->helperText('Complete raw JSON data from analytics source')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
