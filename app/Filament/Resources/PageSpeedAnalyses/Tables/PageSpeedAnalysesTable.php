<?php

namespace App\Filament\Resources\PageSpeedAnalyses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PageSpeedAnalysesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tested_url')
                    ->label('Tesztelt URL')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('project.name')
                    ->label('Projekt')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('keyword.keyword')
                    ->label('Kulcsszó')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('device_type')
                    ->label('Eszköz')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mobile' => 'info',
                        'desktop' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('performance_score')
                    ->label('Teljesítmény')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('seo_score')
                    ->label('SEO')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('lcp')
                    ->label('LCP')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('s')
                    ->sortable()
                    ->badge()
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 2.5 => 'success',
                        $state <= 4.0 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),
                TextColumn::make('fid')
                    ->label('FID')
                    ->numeric()
                    ->suffix('ms')
                    ->sortable()
                    ->badge()
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 100 => 'success',
                        $state <= 300 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),
                TextColumn::make('cls')
                    ->label('CLS')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->badge()
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 0.1 => 'success',
                        $state <= 0.25 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),
                TextColumn::make('load_time')
                    ->label('Betöltési idő')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('s')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('formatted_page_size')
                    ->label('Oldal méret')
                    ->toggleable(),
                TextColumn::make('total_requests')
                    ->label('Kérések')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('analyzed_at')
                    ->label('Elemezve')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('device_type')
                    ->label('Eszköz típus')
                    ->options([
                        'mobile' => 'Mobil',
                        'desktop' => 'Asztali',
                    ]),
                SelectFilter::make('project_id')
                    ->label('Projekt')
                    ->relationship('project', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Sebesség elemzés részletei'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('analyzed_at', 'desc');
    }
}
