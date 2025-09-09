<?php

namespace App\Filament\Resources\PageSpeedResults\Widgets;

use App\Models\PageSpeedResult;
use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PageSpeedRecentResultsTable extends TableWidget
{
    protected static ?string $heading = 'Recent PageSpeed Results';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => PageSpeedResult::query()
                ->forProject(Filament::getTenant() instanceof Project ? Filament::getTenant()->id : 0)
                ->orderBy('analyzed_at', 'desc')
                ->limit(10)
            )
            ->columns([
                TextColumn::make('analyzed_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('strategy')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'mobile' => 'info',
                        'desktop' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('performance_score')
                    ->label('Performance')
                    ->badge()
                    ->suffix('/100')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('seo_score')
                    ->label('SEO')
                    ->badge()
                    ->suffix('/100')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('accessibility_score')
                    ->label('Accessibility')
                    ->badge()
                    ->suffix('/100')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('best_practices_score')
                    ->label('Best Practices')
                    ->badge()
                    ->suffix('/100')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('lcp_display')
                    ->label('LCP')
                    ->badge()
                    ->color(fn ($record): string => match (true) {
                        $record->lcp_score >= 0.9 => 'success',
                        $record->lcp_score >= 0.5 => 'warning',
                        default => 'danger',
                    }),

                TextColumn::make('fcp_display')
                    ->label('FCP')
                    ->badge()
                    ->color(fn ($record): string => match (true) {
                        $record->fcp_score >= 0.9 => 'success',
                        $record->fcp_score >= 0.5 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('strategy')
                    ->options([
                        'mobile' => 'Mobile',
                        'desktop' => 'Desktop',
                    ])
                    ->default('mobile'),
            ])
            ->paginated(false);
    }
}
