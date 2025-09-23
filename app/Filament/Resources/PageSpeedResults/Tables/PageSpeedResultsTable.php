<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSpeedResults\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PageSpeedResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('url')
                    ->label('URL')
                    ->searchable()
                    ->limit(50),

                BadgeColumn::make('strategy')
                    ->label('Strategy')
                    ->colors([
                        'primary' => 'mobile',
                        'secondary' => 'desktop',
                    ]),

                TextColumn::make('performance_score')
                    ->label('Performance')
                    ->formatStateUsing(fn (?int $state): string => $state !== null && $state !== 0 ? $state . '/100' : 'N/A')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('accessibility_score')
                    ->label('Accessibility')
                    ->formatStateUsing(fn (?int $state): string => $state !== null && $state !== 0 ? $state . '/100' : 'N/A')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('seo_score')
                    ->label('SEO')
                    ->formatStateUsing(fn (?int $state): string => $state !== null && $state !== 0 ? $state . '/100' : 'N/A')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('lcp_display')
                    ->label('LCP')
                    ->description('Largest Contentful Paint')
                    ->toggleable(),

                TextColumn::make('fcp_display')
                    ->label('FCP')
                    ->description('First Contentful Paint')
                    ->toggleable(),

                TextColumn::make('cls_display')
                    ->label('CLS')
                    ->description('Cumulative Layout Shift')
                    ->toggleable(),

                TextColumn::make('analyzed_at')
                    ->label('Analyzed')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->analyzed_at->format('Y-m-d H:i:s')),
            ])
            ->filters([
                SelectFilter::make('strategy')
                    ->options([
                        'mobile' => 'Mobile',
                        'desktop' => 'Desktop',
                    ]),

                SelectFilter::make('performance_grade')
                    ->label('Performance Grade')
                    ->options([
                        'excellent' => 'Excellent (90+)',
                        'needs-improvement' => 'Needs Improvement (50-89)',
                        'poor' => 'Poor (<50)',
                    ])
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'excellent' => $query->where('performance_score', '>=', 90),
                            'needs-improvement' => $query->whereBetween('performance_score', [50, 89]),
                            'poor' => $query->where('performance_score', '<', 50),
                            default => $query,
                        };
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) =>
                // Kizárjuk a nagy méretű raw_data mezőt
                $query->select([
                    'page_speed_results.id',
                    'page_speed_results.project_id',
                    'page_speed_results.url',
                    'page_speed_results.strategy',
                    'page_speed_results.performance_score',
                    'page_speed_results.accessibility_score',
                    'page_speed_results.best_practices_score',
                    'page_speed_results.seo_score',
                    'page_speed_results.lcp_display',
                    'page_speed_results.fcp_display',
                    'page_speed_results.cls_display',
                    'page_speed_results.analyzed_at',
                    'page_speed_results.created_at',
                    'page_speed_results.updated_at',
                ]))
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
