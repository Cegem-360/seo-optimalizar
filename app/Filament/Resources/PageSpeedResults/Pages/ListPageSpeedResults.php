<?php

namespace App\Filament\Resources\PageSpeedResults\Pages;

use App\Filament\Resources\PageSpeedResults\PageSpeedResultResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPageSpeedResults extends ListRecords
{
    protected static string $resource = PageSpeedResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dashboard')
                ->label('View Dashboard')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('dashboard')),
            CreateAction::make()
                ->label('Run New Analysis'),
        ];
    }
}
