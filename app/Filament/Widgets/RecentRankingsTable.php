<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\SearchConsoleRanking;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentRankingsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): ?string
    {
        return 'Recent Rankings';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getTableQuery())
            ->columns([
                TextColumn::make('query')
                    ->label('Query')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Current Position')
                    ->badge()
                    ->color(fn ($state): string => $state <= 3 ? 'success' : ($state <= 10 ? 'warning' : 'danger')),

                TextColumn::make('previous_position')
                    ->label('Previous Position')
                    ->placeholder('New')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('change')
                    ->label('Change')
                    ->getStateUsing(function (SearchConsoleRanking $searchConsoleRanking): string {
                        if (! $searchConsoleRanking->previous_position) {
                            return 'NEW';
                        }

                        $change = $searchConsoleRanking->previous_position - $searchConsoleRanking->position;
                        if ($change > 0) {
                            return '+' . $change;
                        }

                        if ($change < 0) {
                            return (string) $change;
                        }

                        return '0';
                    })
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        str_starts_with($state ?? '', '+') || $state === 'NEW' => 'success',
                        str_starts_with($state ?? '', '-') => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->url),

                TextColumn::make('date_to')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('fetched_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('60s');
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Project) {
            return SearchConsoleRanking::query()->whereRaw('1 = 0');
        }

        return SearchConsoleRanking::query()
            ->where('project_id', $tenant->id);
    }
}
