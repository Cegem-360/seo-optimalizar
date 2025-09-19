<?php

namespace App\Filament\Resources\Rankings\Pages;

use App\Filament\Resources\Rankings\RankingResource;
use App\Filament\Resources\Rankings\RankingResource\Widgets\RankingsDateRangeWidget;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRankings extends ListRecords
{
    protected static string $resource = RankingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dashboard')
                ->label('View Dashboard')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('dashboard')),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = $this->getResource()::getEloquentQuery();

        return [
            'all' => Tab::make('All')
                ->badge(fn () => (clone $baseQuery)->count()),
            'today' => Tab::make('Today')
                ->badge(fn () => (clone $baseQuery)->whereDate('checked_at', Carbon::today())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('checked_at', Carbon::today())),
            'week' => Tab::make('This Week')
                ->badge(fn () => (clone $baseQuery)->whereBetween('checked_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ])->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('checked_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ])),
            'month' => Tab::make('This Month')
                ->badge(fn () => (clone $baseQuery)->whereMonth('checked_at', Carbon::now()->month)
                    ->whereYear('checked_at', Carbon::now()->year)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('checked_at', Carbon::now()->month)
                    ->whereYear('checked_at', Carbon::now()->year)),
            'improved' => Tab::make('Improved')
                ->badge(fn () => (clone $baseQuery)->improved()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->improved()),
            'declined' => Tab::make('Declined')
                ->badge(fn () => (clone $baseQuery)->declined()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->declined()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RankingsDateRangeWidget::class,
            RankingResource::getWidgets()[0] ?? null, // RankingsOverviewWidget
        ];
    }
}
