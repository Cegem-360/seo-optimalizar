<?php

namespace App\Filament\Resources\ApiCredentials\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ApiCredentialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service')
                    ->label('API Service')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'google_search_console' => 'Google Search Console',
                        'google_analytics' => 'Google Analytics 4',
                        'google_pagespeed_insights' => 'PageSpeed Insights',
                        'serpapi' => 'SerpAPI',
                        'mobile_friendly_test' => 'Mobile-Friendly Test',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'google_search_console' => 'info',
                        'google_analytics' => 'warning',
                        'google_pagespeed_insights' => 'success',
                        'serpapi' => 'danger',
                        'mobile_friendly_test' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->placeholder('Never used'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('service')
                    ->label('Service Type')
                    ->options([
                        'google_search_console' => 'Google Search Console',
                        'google_analytics' => 'Google Analytics 4',
                        'google_pagespeed_insights' => 'PageSpeed Insights',
                        'serpapi' => 'SerpAPI',
                        'mobile_friendly_test' => 'Mobile-Friendly Test',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
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
