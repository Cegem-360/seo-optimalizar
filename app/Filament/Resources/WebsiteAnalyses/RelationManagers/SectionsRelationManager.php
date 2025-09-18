<?php

namespace App\Filament\Resources\WebsiteAnalyses\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $recordTitleAttribute = 'section_name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('section_name')
                    ->label('Szakasz')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('section_type')
                    ->label('Típus')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('score')
                    ->label('Pontszám')
                    ->numeric()
                    ->sortable()
                    ->suffix('/100')
                    ->color(fn (?int $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Státusz')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'good' => 'success',
                        'warning' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'good' => 'Jó',
                        'warning' => 'Figyelem',
                        'error' => 'Hiba',
                        default => $state,
                    }),

              
            ])
            ->defaultSort('priority')
            ->filters([
                // Add filters here
            ])
            ->recordActions([
                // Add record actions here
            ])
            ->toolbarActions([
                // Add toolbar actions here
            ]);
    }
}
