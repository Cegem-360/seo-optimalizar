<?php

namespace App\Filament\Resources\KeywordResource\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RankingsRelationManager extends RelationManager
{
    protected static string $relationship = 'rankings';

    protected static ?string $title = 'Ranking History';

    protected static ?string $recordTitleAttribute = 'position';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('position')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->label('Position'),

                TextInput::make('url')
                    ->required()
                    ->url()
                    ->maxLength(255)
                    ->label('Ranking URL'),

                Toggle::make('featured_snippet')
                    ->label('Featured Snippet')
                    ->default(false),

                DateTimePicker::make('checked_at')
                    ->label('Checked Date')
                    ->required()
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 3 => 'success',
                        $state <= 10 => 'warning',
                        $state <= 20 => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('change')
                    ->label('Change')
                    ->getStateUsing(function ($record): string {
                        if ($record->previous_position === null) {
                            return 'New';
                        }

                        $change = $record->previous_position - $record->position;
                        if ($change > 0) {
                            return '↑ ' . abs($change);
                        }

                        if ($change < 0) {
                            return '↓ ' . abs($change);
                        }

                        return '–';
                    })
                    ->badge()
                    ->color(function ($state): string {
                        if ($state === 'New') {
                            return 'primary';
                        }

                        if (str_starts_with($state, '↑')) {
                            return 'success';
                        }

                        if (str_starts_with($state, '↓')) {
                            return 'danger';
                        }

                        return 'gray';
                    }),

                TextColumn::make('url')
                    ->limit(50)
                    ->searchable()
                    ->copyable()
                    ->tooltip(fn ($record) => $record->url),

                IconColumn::make('featured_snippet')
                    ->boolean()
                    ->label('Snippet'),

                TextColumn::make('serp_features')
                    ->label('CTR')
                    ->getStateUsing(function ($record): string {
                        $features = json_decode((string) $record->serp_features, true);

                        return isset($features['ctr'])
                            ? round($features['ctr'] * 100, 2) . '%'
                            : '–';
                    })
                    ->badge()
                    ->color('primary'),

                TextColumn::make('checked_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Checked'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('top_10')
                    ->query(fn (Builder $builder): Builder => $builder->where('position', '<=', 10))
                    ->label('Top 10'),

                Filter::make('featured_snippet')
                    ->query(fn (Builder $builder): Builder => $builder->where('featured_snippet', true))
                    ->label('Has Featured Snippet'),

                Filter::make('last_30_days')
                    ->query(fn (Builder $builder): Builder => $builder->where('checked_at', '>=', now()->subDays(30)))
                    ->label('Last 30 Days'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('checked_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
