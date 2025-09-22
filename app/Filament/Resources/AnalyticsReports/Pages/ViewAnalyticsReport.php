<?php

namespace App\Filament\Resources\AnalyticsReports\Pages;

use App\Filament\Resources\AnalyticsReports\AnalyticsReportResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAnalyticsReport extends ViewRecord
{
    protected static string $resource = AnalyticsReportResource::class;

    protected function getActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Report Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('project.name')
                                    ->label('Project'),
                                TextEntry::make('report_date')
                                    ->label('Report Date')
                                    ->date(),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                            ]),
                    ]),

                Section::make('Key Metrics')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('sessions')
                                    ->label('Sessions')
                                    ->numeric()
                                    ->color('primary'),
                                TextEntry::make('active_users')
                                    ->label('Active Users')
                                    ->numeric(),
                                TextEntry::make('new_users')
                                    ->label('New Users')
                                    ->numeric()
                                    ->color('success'),
                                TextEntry::make('conversions')
                                    ->label('Conversions')
                                    ->numeric()
                                    ->color('warning'),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('bounce_rate')
                                    ->label('Bounce Rate')
                                    ->numeric(decimalPlaces: 1)
                                    ->suffix('%')
                                    ->color(fn ($state) => $state > 70 ? 'danger' : ($state > 50 ? 'warning' : 'success')),
                                TextEntry::make('average_session_duration')
                                    ->label('Avg Session Duration')
                                    ->numeric(decimalPlaces: 0)
                                    ->suffix(' seconds'),
                                TextEntry::make('screen_page_views')
                                    ->label('Page Views')
                                    ->numeric(),
                                TextEntry::make('mobile_traffic_percentage')
                                    ->label('Mobile Traffic')
                                    ->getStateUsing(fn ($record) => round($record->getMobileTrafficPercentage(), 1))
                                    ->suffix('%'),
                            ]),
                    ]),

                Section::make('Top Traffic Sources')
                    ->description('Breakdown of traffic by channel')
                    ->schema([
                        TextEntry::make('top_traffic_source')
                            ->label('Top Traffic Source')
                            ->getStateUsing(function ($record) {
                                $topSource = $record->getTopTrafficSource();
                                return $topSource ?
                                    $topSource['sessionDefaultChannelGroup'] . ' (' . number_format($topSource['sessions']) . ' sessions)' :
                                    'No data available';
                            }),
                    ])
                    ->collapsible(),

                Section::make('Top Pages')
                    ->description('Best performing pages by views')
                    ->schema([
                        TextEntry::make('top_page')
                            ->label('Top Page')
                            ->getStateUsing(function ($record) {
                                $topPage = $record->getTopPage();
                                return $topPage ?
                                    $topPage['pageTitle'] . ' (' . number_format($topPage['screenPageViews']) . ' views)' :
                                    'No data available';
                            }),
                    ])
                    ->collapsible(),

                Section::make('Raw Analytics Data')
                    ->description('Complete analytics data in JSON format')
                    ->schema([
                        TextEntry::make('traffic_sources')
                            ->label('Traffic Sources')
                            ->getStateUsing(fn ($record) => json_encode($record->traffic_sources, JSON_PRETTY_PRINT))
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('top_pages')
                            ->label('Top Pages')
                            ->getStateUsing(fn ($record) => json_encode($record->top_pages, JSON_PRETTY_PRINT))
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('device_data')
                            ->label('Device Data')
                            ->getStateUsing(fn ($record) => json_encode($record->device_data, JSON_PRETTY_PRINT))
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}