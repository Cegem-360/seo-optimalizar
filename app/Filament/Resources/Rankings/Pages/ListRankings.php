<?php

namespace App\Filament\Resources\Rankings\Pages;

use App\Filament\Resources\Rankings\RankingResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

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
}
