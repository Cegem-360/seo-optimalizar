<?php

namespace App\Filament\Resources\Keywords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KeywordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('keyword')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('intent_type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'informational' => 'info',
                        'navigational' => 'warning',
                        'commercial' => 'success',
                        'transactional' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('search_volume')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('difficulty_score')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('geo_target')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('rankings_count')
                    ->counts('rankings')
                    ->badge()
                    ->color('secondary'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->options([
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),

                SelectFilter::make('intent_type')
                    ->options([
                        'informational' => 'Informational',
                        'navigational' => 'Navigational',
                        'commercial' => 'Commercial',
                        'transactional' => 'Transactional',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
