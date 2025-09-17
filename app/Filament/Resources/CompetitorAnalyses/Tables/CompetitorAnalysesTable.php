<?php

namespace App\Filament\Resources\CompetitorAnalyses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompetitorAnalysesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('competitor_domain')
                    ->label('Versenytárs domain')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('keyword.keyword')
                    ->label('Kulcsszó')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Pozíció')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 3 => 'success',
                        $state <= 10 => 'info',
                        $state <= 20 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('domain_authority')
                    ->label('Domain Authority')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 80 => 'success',
                        $state >= 60 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('page_authority')
                    ->label('Page Authority')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('backlinks_count')
                    ->label('Backlink-ek')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state) : 'N/A'),
                TextColumn::make('page_speed_score')
                    ->label('Sebesség')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->toggleable(),
                TextColumn::make('content_length')
                    ->label('Tartalom hossz')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state) . ' karakter' : 'N/A')
                    ->toggleable(),
                TextColumn::make('has_ssl')
                    ->label('SSL')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Igen' : 'Nem')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->toggleable(),
                TextColumn::make('is_mobile_friendly')
                    ->label('Mobilbarát')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Igen' : 'Nem')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->toggleable(),
                TextColumn::make('has_schema_markup')
                    ->label('Schema')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Van' : 'Nincs')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->toggleable(),
                TextColumn::make('strength_score')
                    ->label('Erősség pontszám')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'info',
                        $state >= 40 => 'warning',
                        default => 'danger',
                    })
                    ->suffix('%'),
                TextColumn::make('analyzed_at')
                    ->label('Elemezve')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('position')
                    ->label('Pozíció kategória')
                    ->options([
                        '1-3' => 'Top 3 (1-3)',
                        '4-10' => 'Első oldal (4-10)',
                        '11-20' => 'Második oldal (11-20)',
                        '21+' => '21+ pozíció',
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            '1-3' => $query->whereBetween('position', [1, 3]),
                            '4-10' => $query->whereBetween('position', [4, 10]),
                            '11-20' => $query->whereBetween('position', [11, 20]),
                            '21+' => $query->where('position', '>', 20),
                            default => $query,
                        };
                    }),
                SelectFilter::make('domain_authority_range')
                    ->label('Domain Authority')
                    ->options([
                        '80+' => 'Nagyon magas (80+)',
                        '60-79' => 'Magas (60-79)',
                        '40-59' => 'Közepes (40-59)',
                        '0-39' => 'Alacsony (0-39)',
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            '80+' => $query->where('domain_authority', '>=', 80),
                            '60-79' => $query->whereBetween('domain_authority', [60, 79]),
                            '40-59' => $query->whereBetween('domain_authority', [40, 59]),
                            '0-39' => $query->whereBetween('domain_authority', [0, 39]),
                            default => $query,
                        };
                    }),
                SelectFilter::make('has_ssl')
                    ->label('SSL tanúsítvány')
                    ->options([
                        1 => 'Van SSL',
                        0 => 'Nincs SSL',
                    ]),
                SelectFilter::make('is_mobile_friendly')
                    ->label('Mobilbarát')
                    ->options([
                        1 => 'Mobilbarát',
                        0 => 'Nem mobilbarát',
                    ]),
                SelectFilter::make('project_id')
                    ->label('Projekt')
                    ->relationship('project', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalHeading('Versenytárs elemzés részletei'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('analyzed_at', 'desc');
    }
}
