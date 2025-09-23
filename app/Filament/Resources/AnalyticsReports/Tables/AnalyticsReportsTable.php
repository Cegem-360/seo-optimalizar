<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalyticsReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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
                    ->color(fn ($state): string => $state > 70 ? 'danger' : ($state > 50 ? 'warning' : 'success')),

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
                    ->getStateUsing(fn ($record): float => round($record->getMobileTrafficPercentage(), 1))
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
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(fn (Builder $builder, array $data): Builder => $builder
                        ->when(
                            $data['from'],
                            fn (Builder $builder, $date): Builder => $builder->whereDate('report_date', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $builder, $date): Builder => $builder->whereDate('report_date', '<=', $date),
                        )),

                Filter::make('high_traffic')
                    ->label('High Traffic Days')
                    ->query(fn (Builder $builder): Builder => $builder->where('sessions', '>', 1000))
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
