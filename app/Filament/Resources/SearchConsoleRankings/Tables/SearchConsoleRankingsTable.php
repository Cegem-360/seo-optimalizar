<?php

namespace App\Filament\Resources\SearchConsoleRankings\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SearchConsoleRankingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date_range')
                    ->label('Period')
                    ->badge()
                    ->color('info')
                    ->sortable(['date_from', 'date_to']),

                TextColumn::make('query')
                    ->label('Query')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->query),

                TextColumn::make('page')
                    ->label('URL')
                    ->searchable()
                    ->limit(30)
                    ->formatStateUsing(fn ($state) => parse_url($state, PHP_URL_PATH) ?? $state)
                    ->tooltip(fn ($record) => $record->page)
                    ->url(fn ($record) => $record->page, true),

                TextColumn::make('position')
                    ->label('Position')
                    ->sortable()
                    ->numeric(2)
                    ->badge()
                    ->color(fn ($record) => $record->getPositionBadgeColor())
                    ->formatStateUsing(fn ($state) => number_format($state, 1)),

                TextColumn::make('position_change')
                    ->label('Change')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if ($state === null) {
                            return 'NEW';
                        }
                        if ($state > 0) {
                            return '↑ ' . abs($state);
                        }
                        if ($state < 0) {
                            return '↓ ' . abs($state);
                        }

                        return '→ 0';
                    })
                    ->color(fn ($state) => match (true) {
                        $state === null => 'info',
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('clicks')
                    ->label('Clicks')
                    ->sortable()
                    ->numeric()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('clicks_change_percent')
                    ->label('Click Δ%')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if ($state === null) {
                            return '-';
                        }

                        return ($state > 0 ? '+' : '') . number_format($state, 1) . '%';
                    })
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('impressions')
                    ->label('Impressions')
                    ->sortable()
                    ->numeric()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('ctr')
                    ->label('CTR')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 2) . '%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 0.10 => 'success',
                        $state >= 0.05 => 'warning',
                        $state >= 0.02 => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('device')
                    ->label('Device')
                    ->badge()
                    ->icon(fn ($state) => match ($state) {
                        'mobile' => 'heroicon-m-device-phone-mobile',
                        'tablet' => 'heroicon-m-device-tablet',
                        'desktop' => 'heroicon-m-computer-desktop',
                        default => null,
                    }),

                TextColumn::make('country')
                    ->label('Country')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('fetched_at')
                    ->label('Fetched')
                    ->dateTime('M d, H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->fetched_at?->diffForHumans()),
            ])
            ->defaultSort('position', 'asc')
            ->filters([
                // Date Range Filter
                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From')
                            ->default(Carbon::now()->subDays(30)),
                        DatePicker::make('date_to')
                            ->label('To')
                            ->default(Carbon::now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->where('date_from', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->where('date_to', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['date_from'] ?? null) {
                            $indicators[] = Indicator::make('From: ' . Carbon::parse($data['date_from'])->format('M d, Y'))
                                ->removeField('date_from');
                        }

                        if ($data['date_to'] ?? null) {
                            $indicators[] = Indicator::make('To: ' . Carbon::parse($data['date_to'])->format('M d, Y'))
                                ->removeField('date_to');
                        }

                        return $indicators;
                    }),

                // Position Range Filter
                SelectFilter::make('position_range')
                    ->label('Position Range')
                    ->options([
                        'top3' => 'Top 3',
                        'top10' => 'Top 10 (First Page)',
                        '11-20' => 'Position 11-20 (Second Page)',
                        '21-50' => 'Position 21-50',
                        '50+' => 'Beyond 50',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'top3' => $query->where('position', '<=', 3),
                        'top10' => $query->where('position', '<=', 10),
                        '11-20' => $query->whereBetween('position', [10.01, 20]),
                        '21-50' => $query->whereBetween('position', [20.01, 50]),
                        '50+' => $query->where('position', '>', 50),
                        default => $query,
                    }),

                // Device Filter
                SelectFilter::make('device')
                    ->label('Device')
                    ->options([
                        'desktop' => 'Desktop',
                        'mobile' => 'Mobile',
                        'tablet' => 'Tablet',
                    ]),

                // Country Filter
                SelectFilter::make('country')
                    ->label('Country')
                    ->options([
                        'hun' => 'Hungary',
                        'usa' => 'United States',
                        'gbr' => 'United Kingdom',
                        'deu' => 'Germany',
                        'aut' => 'Austria',
                    ]),

                // Trend Filter
                SelectFilter::make('trend')
                    ->label('Position Trend')
                    ->options([
                        'improved' => 'Improved ↑',
                        'declined' => 'Declined ↓',
                        'stable' => 'Stable →',
                        'new' => 'New',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'improved' => $query->improved(),
                        'declined' => $query->declined(),
                        'stable' => $query->where('position_change', 0),
                        'new' => $query->whereNull('previous_position'),
                        default => $query,
                    }),

                // Clicks Filter
                Filter::make('has_clicks')
                    ->label('Has Clicks')
                    ->query(fn (Builder $query): Builder => $query->withClicks()),

                // Recent Filter
                Filter::make('recent')
                    ->label('Recent (Last 7 days)')
                    ->query(fn (Builder $query): Builder => $query->where('fetched_at', '>=', Carbon::now()->subDays(7))),
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
            ->emptyStateHeading('No Search Console data found')
            ->emptyStateDescription('Import or sync data from Google Search Console to see rankings here.')
            ->emptyStateIcon('heroicon-o-magnifying-glass-circle');
    }
}
