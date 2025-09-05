<?php

namespace App\Filament\Resources\PageSpeedResults\Pages;

use App\Filament\Resources\PageSpeedResults\PageSpeedResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPageSpeedResults extends ListRecords
{
    protected static string $resource = PageSpeedResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
