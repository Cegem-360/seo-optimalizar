<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Ranking;
use Filament\Facades\Filament;
use Filament\Tables\Columns\BadgeColumn;
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
                TextColumn::make('keyword.keyword')
                    ->label('Keyword')
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

                BadgeColumn::make('change')
                    ->label('Change')
                    ->getStateUsing(function (Ranking $ranking): string {
                        if (! $ranking->previous_position) {
                            return 'NEW';
                        }

                        $change = $ranking->previous_position - $ranking->position;
                        if ($change > 0) {
                            return '+' . $change;
                        }

                        if ($change < 0) {
                            return (string) $change;
                        }

                        return '0';
                    })
                    ->colors([
                        'success' => fn ($state): bool => str_starts_with($state ?? '', '+') || $state === 'NEW',
                        'danger' => fn ($state): bool => str_starts_with($state ?? '', '-'),
                        'gray' => fn ($state): bool => $state === '0',
                    ]),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->url),

                TextColumn::make('checked_at')
                    ->label('Checked At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('checked_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('60s');
    }

    protected function getTableQuery(): Builder
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Project) {
            return Ranking::query()->whereRaw('1 = 0');
        }

        return Ranking::query()
            ->with(['keyword'])
            ->whereHas('keyword', function ($query) use ($tenant): void {
                $query->where('project_id', $tenant->id);
            });
    }
}
