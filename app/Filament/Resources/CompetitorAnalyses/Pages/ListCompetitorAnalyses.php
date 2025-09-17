<?php

namespace App\Filament\Resources\CompetitorAnalyses\Pages;

use App\Filament\Resources\CompetitorAnalyses\CompetitorAnalysisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompetitorAnalyses extends ListRecords
{
    protected static string $resource = CompetitorAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
