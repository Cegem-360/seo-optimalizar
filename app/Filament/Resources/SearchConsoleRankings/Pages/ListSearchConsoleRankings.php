<?php

namespace App\Filament\Resources\SearchConsoleRankings\Pages;

use App\Filament\Resources\SearchConsoleRankings\SearchConsoleRankingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSearchConsoleRankings extends ListRecords
{
    protected static string $resource = SearchConsoleRankingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
