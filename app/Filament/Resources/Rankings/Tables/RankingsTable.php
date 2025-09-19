<?php

namespace App\Filament\Resources\Rankings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\Indicator;

class RankingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('keyword.keyword')
                    ->label('Keyword')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->keyword->category ?? null),

                TextColumn::make('position')
                    ->label('Position')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 3 => 'success',
                        $state <= 10 => 'warning',
                        $state <= 20 => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => '#' . $state),

                TextColumn::make('position_change')
                    ->label('Change')
                    ->badge()
                    ->formatStateUsing(function ($record): string {
                        $change = $record->position_change;
                        if ($change === null) {
                            return 'New';
                        }

                        if ($change > 0) {
                            return '+' . $change;
                        }

                        if ($change < 0) {
                            return (string) $change;
                        }

                        return '0';
                    })
                    ->color(function ($record): string {
                        $trend = $record->position_trend;

                        return match ($trend) {
                            'up' => 'success',
                            'down' => 'danger',
                            'new' => 'info',
                            default => 'gray',
                        };
                    })
                    ->icon(function ($record): ?string {
                        $trend = $record->position_trend;

                        return match ($trend) {
                            'up' => 'heroicon-o-arrow-trending-up',
                            'down' => 'heroicon-o-arrow-trending-down',
                            'new' => 'heroicon-o-sparkles',
                            'same' => 'heroicon-o-minus',
                            default => null,
                        };
                    }),

                TextColumn::make('previous_position')
                    ->label('Previous')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => $state ? '#' . $state : '-'),

                TextColumn::make('url')
                    ->label('Ranking URL')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->copyable()
                    ->copyMessage('URL copied')
                    ->copyMessageDuration(1500),

                IconColumn::make('featured_snippet')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('serp_features')
                    ->label('Search Console Data')
                    ->formatStateUsing(function ($state): string {
                        if (empty($state)) {
                            return '-';
                        }

                        // Debug: nézzük meg mi van a state-ben
                        $originalState = $state;

                        // Dekódoljuk a dupla JSON string-et
                        if (is_string($state)) {
                            // Először próbáljuk meg közvetlenül dekódolni
                            $firstTry = json_decode($state, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                if (is_array($firstTry)) {
                                    $state = $firstTry;
                                } elseif (is_string($firstTry)) {
                                    // Ha string, próbáljuk újra dekódolni
                                    $secondTry = json_decode($firstTry, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($secondTry)) {
                                        $state = $secondTry;
                                    }
                                }
                            }
                        }

                        // Ha még mindig array-t kaptunk
                        if (is_array($state) && $state !== []) {
                            $metrics = [];

                            // Clicks (Kattintások)
                            if (isset($state['clicks']) && is_numeric($state['clicks'])) {
                                $metrics[] = "<div class='flex items-center gap-1'><span class='font-semibold text-xs text-gray-600 dark:text-gray-400'>Clicks:</span><span class='font-medium'>" . number_format($state['clicks']) . '</span></div>';
                            }

                            // Impressions (Megjelenítések)
                            if (isset($state['impressions']) && is_numeric($state['impressions'])) {
                                $metrics[] = "<div class='flex items-center gap-1'><span class='font-semibold text-xs text-gray-600 dark:text-gray-400'>Views:</span><span class='font-medium'>" . number_format($state['impressions']) . '</span></div>';
                            }

                            // CTR (Click Through Rate)
                            if (isset($state['ctr']) && is_numeric($state['ctr'])) {
                                $ctr = round($state['ctr'] * 100, 2);
                                $metrics[] = "<div class='flex items-center gap-1'><span class='font-semibold text-xs text-gray-600 dark:text-gray-400'>CTR:</span><span class='font-medium'>" . $ctr . '%</span></div>';
                            }

                            if ($metrics !== []) {
                                return "<div class='space-y-1'>" . implode('', $metrics) . '</div>';
                            }
                        }

                        // Fallback: ha semmi nem működött, mutassuk az eredeti értéket debug céljából
                        return is_string($originalState) ? 'Raw: ' . substr($originalState, 0, 50) . '...' : '-';
                    })
                    ->html()
                    ->wrap(),

                TextColumn::make('keyword.search_volume')
                    ->label('Search Vol.')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(function ($state): string {
                        if ($state === null || $state === '') {
                            return '-';
                        }

                        return number_format($state);
                    })
                    ->color(fn ($state): string => $state !== null && $state !== '' ? 'primary' : 'gray')
                    ->placeholder('-')
                    ->description('Not available'),

                TextColumn::make('keyword.difficulty_score')
                    ->label('Difficulty')
                    ->sortable()
                    ->formatStateUsing(function (?string $state): string {
                        if ($state === null || $state === '') {
                            return 'N/A';
                        }

                        return $state . '/100';
                    })
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === null || $state === '' => 'gray',
                        $state <= 30 => 'success',
                        $state <= 60 => 'warning',
                        default => 'danger',
                    })
                    ->placeholder('N/A'),

                TextColumn::make('keyword.priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('checked_at')
                    ->label('Last Check')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->checked_at?->diffForHumans()),
            ])
            ->defaultSort('position', 'asc')
            ->filters([
                SelectFilter::make('position_range')
                    ->label('Position Range')
                    ->options([
                        'top3' => 'Top 3',
                        'top10' => 'Top 10',
                        '11-20' => 'Position 11-20',
                        '21-50' => 'Position 21-50',
                        '50+' => 'Beyond 50',
                    ])
                    ->query(fn (Builder $builder, array $data): Builder => match ($data['value'] ?? null) {
                        'top3' => $builder->where('position', '<=', 3),
                        'top10' => $builder->where('position', '<=', 10),
                        '11-20' => $builder->whereBetween('position', [11, 20]),
                        '21-50' => $builder->whereBetween('position', [21, 50]),
                        '50+' => $builder->where('position', '>', 50),
                        default => $builder,
                    }),

                SelectFilter::make('trend')
                    ->label('Trend')
                    ->options([
                        'improved' => 'Improved',
                        'declined' => 'Declined',
                        'new' => 'New',
                        'unchanged' => 'Unchanged',
                    ])
                    ->query(fn (Builder $builder, array $data): Builder => match ($data['value'] ?? null) {
                        'improved' => $builder->improved(),
                        'declined' => $builder->declined(),
                        'new' => $builder->whereNull('previous_position'),
                        'unchanged' => $builder->whereColumn('position', 'previous_position'),
                        default => $builder,
                    }),

                SelectFilter::make('priority')
                    ->label('Priority')
                    ->relationship('keyword', 'priority')
                    ->options([
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),

                Filter::make('featured_snippet')
                    ->label('Featured Snippet')
                    ->query(fn (Builder $builder): Builder => $builder->where('featured_snippet', true)),

                Filter::make('recent')
                    ->label('Checked in last 7 days')
                    ->query(fn (Builder $builder): Builder => $builder->recentlyChecked(7)),

                Filter::make('checked_at')
                    ->label('Date Range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('checked_from')
                            ->label('From')
                            ->placeholder('Select start date'),
                        \Filament\Forms\Components\DatePicker::make('checked_until')
                            ->label('Until')
                            ->placeholder('Select end date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['checked_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('checked_at', '>=', $date),
                            )
                            ->when(
                                $data['checked_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('checked_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['checked_from'] ?? null) {
                            $indicators[] = Indicator::make('From: ' . \Carbon\Carbon::parse($data['checked_from'])->format('M d, Y'))
                                ->removeField('checked_from');
                        }

                        if ($data['checked_until'] ?? null) {
                            $indicators[] = Indicator::make('Until: ' . \Carbon\Carbon::parse($data['checked_until'])->format('M d, Y'))
                                ->removeField('checked_until');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No rankings found')
            ->emptyStateDescription('Start tracking keywords to see ranking data here.')
            ->emptyStateIcon('heroicon-o-magnifying-glass');
    }
}
