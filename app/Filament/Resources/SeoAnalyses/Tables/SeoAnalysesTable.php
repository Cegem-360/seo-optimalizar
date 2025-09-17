<?php

namespace App\Filament\Resources\SeoAnalyses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeoAnalysesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('keyword.keyword')
                    ->label('Kulcsszó')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.name')
                    ->label('Projekt')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('competition_level')
                    ->label('Verseny szint')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('search_intent')
                    ->label('Keresési szándék')
                    ->badge(),
                TextColumn::make('position_rating')
                    ->label('Pozíció értékelés')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'kiváló' => 'success',
                        'jó' => 'info',
                        'közepes' => 'warning',
                        'gyenge' => 'warning',
                        'kritikus' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('current_position')
                    ->label('Jelenlegi pozíció')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 3 => 'success',
                        $state <= 10 => 'info',
                        $state <= 20 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('target_position')
                    ->label('Cél pozíció')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_timeframe')
                    ->label('Becsült időkeret')
                    ->searchable(),
                TextColumn::make('main_competitors')
                    ->label('Fő versenytársak')
                    ->badge()
                    ->separator(', ')
                    ->limit(3),
                TextColumn::make('created_at')
                    ->label('Létrehozva')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('competition_level')
                    ->label('Verseny szint')
                    ->options([
                        'low' => 'Alacsony',
                        'medium' => 'Közepes',
                        'high' => 'Magas',
                    ]),
                SelectFilter::make('search_intent')
                    ->label('Keresési szándék')
                    ->options([
                        'informational' => 'Információs',
                        'commercial' => 'Kereskedelmi',
                        'transactional' => 'Tranzakciós',
                        'navigational' => 'Navigációs',
                    ]),
                SelectFilter::make('position_rating')
                    ->label('Pozíció értékelés')
                    ->options([
                        'kiváló' => 'Kiváló',
                        'jó' => 'Jó',
                        'közepes' => 'Közepes',
                        'gyenge' => 'Gyenge',
                        'kritikus' => 'Kritikus',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('SEO elemzés részletei'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
