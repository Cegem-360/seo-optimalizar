<?php

namespace App\Filament\Resources\AnalyticsReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('report_date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->description(fn ($record) => $record->report_date->diffForHumans()),

                TextColumn::make('sessions')
                    ->label('Sessions')
                    ->numeric()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('active_users')
                    ->label('Active Users')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('new_users')
                    ->label('New Users')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->toggleable(),

                TextColumn::make('bounce_rate')
                    ->label('Bounce Rate')
                    ->numeric(decimalPlaces: 1)
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => $state > 70 ? 'danger' : ($state > 50 ? 'warning' : 'success')),

                TextColumn::make('average_session_duration')
                    ->label('Avg Duration')
                    ->numeric(decimalPlaces: 0)
                    ->suffix('s')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('screen_page_views')
                    ->label('Page Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('conversions')
                    ->label('Conversions')
                    ->numeric()
                    ->sortable()
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('mobile_traffic_percentage')
                    ->label('Mobile %')
                    ->getStateUsing(fn ($record) => round($record->getMobileTrafficPercentage(), 1))
                    ->suffix('%')
                    ->sortable(false)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('date_range')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('report_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('report_date', '<=', $date),
                            );
                    }),

                Filter::make('high_traffic')
                    ->label('High Traffic Days')
                    ->query(fn (Builder $query): Builder => $query->where('sessions', '>', 1000))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('report_date', 'desc')
            ->striped()
            ->emptyStateHeading('No analytics reports yet')
            ->emptyStateDescription('Start collecting analytics data by running: php artisan analytics:collect-daily');
    }
}
