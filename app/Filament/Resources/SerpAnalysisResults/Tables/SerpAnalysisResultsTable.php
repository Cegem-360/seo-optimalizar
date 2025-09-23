<?php

declare(strict_types=1);

namespace App\Filament\Resources\SerpAnalysisResults\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SerpAnalysisResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('keyword.keyword')
                    ->label('Kulcsszó')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('analysis_data.current_position')
                    ->label('Pozíció')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => $state ? '#' . $state : 'N/A'),

                TextColumn::make('analysis_data.position_rating')
                    ->label('Értékelés')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'kiváló' => 'success',
                        'jó' => 'info',
                        'közepes' => 'warning',
                        'gyenge' => 'danger',
                        'kritikus' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('analysis_data.target_position')
                    ->label('Cél pozíció')
                    ->numeric()
                    ->formatStateUsing(fn ($state): string => $state ? '#' . $state : 'N/A'),

                TextColumn::make('analysis_data.estimated_timeframe')
                    ->label('Időtáv')
                    ->wrap(),

                TextColumn::make('serp_metrics.total_results')
                    ->label('Találatok')
                    ->numeric()
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state) : 'N/A'),

                TextColumn::make('created_at')
                    ->label('Elemzés dátuma')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('analysis_data.position_rating')
                    ->label('Értékelés')
                    ->options([
                        'kiváló' => 'Kiváló',
                        'jó' => 'Jó',
                        'közepes' => 'Közepes',
                        'gyenge' => 'Gyenge',
                        'kritikus' => 'Kritikus',
                    ]),

                SelectFilter::make('keyword')
                    ->relationship('keyword', 'keyword')
                    ->label('Kulcsszó')
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('created_at', 'desc');
    }
}
