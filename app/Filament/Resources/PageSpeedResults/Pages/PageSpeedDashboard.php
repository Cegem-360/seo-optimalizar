<?php

namespace App\Filament\Resources\PageSpeedResults\Pages;

use App\Filament\Resources\PageSpeedResults\PageSpeedResultResource;
use Filament\Actions\Action;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Resources\Pages\Page;

class PageSpeedDashboard extends Page
{
    use InteractsWithHeaderActions;
    
    protected static string $resource = PageSpeedResultResource::class;
    
    protected static ?string $title = 'PageSpeed Dashboard';
    
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?string $slug = 'dashboard';
    
    protected ?string $heading = 'PageSpeed Analytics Dashboard';
    
    protected ?string $subheading = 'Performance metrics and trends overview';
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('runAnalysis')
                ->label('Run New Analysis')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->url(fn () => static::getResource()::getUrl('create')),
            Action::make('viewResults')
                ->label('View All Results')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            PageSpeedResultResource::getWidgets()[0], // PageSpeedOverviewWidget
        ];
    }
    
    protected function getWidgets(): array
    {
        return [
            PageSpeedResultResource::getWidgets()[1], // PageSpeedTrendChart
            PageSpeedResultResource::getWidgets()[2], // PageSpeedRecentResultsTable
        ];
    }
    
    public function getWidgetData(): array
    {
        return [];
    }
}
