<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class KeywordPerformanceWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Performing Keywords';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $tenant = Filament::getTenant();

                if (! $tenant) {
                    return \App\Models\Keyword::query()->whereRaw('1 = 0');
                }

                return \App\Models\Keyword::query()
                    ->where('project_id', $tenant->id)
                    ->withAvg('rankings', 'position')
                    ->withCount('rankings')
                    ->having('rankings_count', '>', 0)
                    ->orderBy('rankings_avg_position', 'asc');
            })
            ->columns([
                TextColumn::make('keyword')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('category')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('rankings_avg_position')
                    ->label('Avg Position')
                    ->numeric(1)
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state <= 3 => 'success',
                        $state <= 10 => 'warning',
                        $state <= 20 => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('latest_position')
                    ->label('Current Position')
                    ->getStateUsing(function ($record) {
                        $latestRanking = $record->rankings()
                            ->orderBy('checked_at', 'desc')
                            ->first();

                        return $latestRanking?->position ?? '–';
                    })
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === '–' => 'gray',
                        $state <= 3 => 'success',
                        $state <= 10 => 'warning',
                        default => 'info',
                    }),

                TextColumn::make('position_change')
                    ->label('Change (7d)')
                    ->getStateUsing(function ($record) {
                        $latest = $record->rankings()
                            ->orderBy('checked_at', 'desc')
                            ->first();

                        $weekAgo = $record->rankings()
                            ->where('checked_at', '<=', now()->subDays(7))
                            ->orderBy('checked_at', 'desc')
                            ->first();

                        if (! $latest || ! $weekAgo) {
                            return '–';
                        }

                        $change = $weekAgo->position - $latest->position;

                        if ($change > 0) {
                            return '↑ ' . abs($change);
                        } elseif ($change < 0) {
                            return '↓ ' . abs($change);
                        }

                        return '–';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state === '–') {
                            return 'gray';
                        }
                        if (str_starts_with($state, '↑')) {
                            return 'success';
                        }
                        if (str_starts_with($state, '↓')) {
                            return 'danger';
                        }

                        return 'gray';
                    }),

                TextColumn::make('search_volume')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('difficulty_score')
                    ->label('Difficulty')
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state <= 30 => 'success',
                        $state <= 60 => 'warning',
                        default => 'danger',
                    })
                    ->sortable()
                    ->toggleable(),
            ])
            ->paginated([5, 10])
            ->defaultSort('rankings_avg_position', 'asc');
    }
}
