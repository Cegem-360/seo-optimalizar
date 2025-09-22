<?php

namespace App\Filament\Resources\GoogleAdsReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GoogleAdsReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('report_date')
                    ->label('Report Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('keyword_count')
                    ->label('Keywords')
                    ->getStateUsing(fn($record) => count($record->keyword_data ?? []))
                    ->badge()
                    ->color('primary'),

                TextColumn::make('successful_fetches')
                    ->label('Successful')
                    ->getStateUsing(fn($record) => $record->statistics['successful_fetches'] ?? 0)
                    ->badge()
                    ->color('success'),

                TextColumn::make('connection_status')
                    ->label('Source')
                    ->getStateUsing(fn($record) => $record->metadata['actual_status'] ?? false ? 'API' : 'Mock')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'API' ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('project')
                    ->relationship('project', 'name'),
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
            ->defaultSort('report_date', 'desc');
    }
}
