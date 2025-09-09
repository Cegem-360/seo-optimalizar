<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Jobs\ImportSearchConsoleDataJob;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('url')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(),

                TextColumn::make('keywords_count')
                    ->counts('keywords')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('import_search_console')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->label('Import from Search Console')
                    ->action(function ($record): void {
                        if ($record instanceof \App\Models\Project) {
                            ImportSearchConsoleDataJob::dispatch($record);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Import Search Console Data')
                    ->modalDescription('This will import keyword rankings from Google Search Console for this project.')
                    ->modalSubmitActionLabel('Import Data')
                    ->color('success'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('import_search_console_bulk')
                        ->icon('heroicon-o-cloud-arrow-down')
                        ->label('Import from Search Console')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                if ($record instanceof \App\Models\Project) {
                                    ImportSearchConsoleDataJob::dispatch($record);
                                }
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Import Search Console Data')
                        ->modalDescription('This will import keyword rankings from Google Search Console for the selected projects.')
                        ->modalSubmitActionLabel('Import Data')
                        ->color('success'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
