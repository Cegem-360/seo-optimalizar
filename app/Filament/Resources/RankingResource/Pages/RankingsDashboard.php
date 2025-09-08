<?php

namespace App\Filament\Resources\RankingResource\Pages;

use App\Filament\Resources\RankingResource;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Resources\Pages\Page;

class RankingsDashboard extends Page
{
    use InteractsWithHeaderActions;
    
    protected static string $resource = RankingResource::class;
    
    protected static ?string $title = 'Rankings Dashboard';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?string $slug = 'dashboard';
    
    protected ?string $heading = 'Rankings Analytics Dashboard';
    
    protected ?string $subheading = 'Keyword ranking performance and trends overview';
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewRankings')
                ->label('View All Rankings')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),
            Action::make('addKeyword')
                ->label('Track New Keyword')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn () => static::getResource()::getUrl('create')),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            RankingResource::getWidgets()[0], // RankingsOverviewWidget
        ];
    }
    
    protected function getWidgets(): array
    {
        return [
            RankingResource::getWidgets()[1], // RankingsTrendChart
            RankingResource::getWidgets()[2], // RankingsDistributionChart
        ];
    }
    
    public function getWidgetData(): array
    {
        return [];
    }
}
