<?php

namespace App\Filament\Resources\PageSpeedAnalyses\Pages;

use App\Filament\Resources\PageSpeedAnalyses\PageSpeedAnalysisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPageSpeedAnalyses extends ListRecords
{
    protected static string $resource = PageSpeedAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
