<?php

namespace App\Filament\Resources\SeoAnalyses\Pages;

use App\Filament\Resources\SeoAnalyses\SeoAnalysisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeoAnalyses extends ListRecords
{
    protected static string $resource = SeoAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
