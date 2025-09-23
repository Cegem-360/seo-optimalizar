<?php

declare(strict_types=1);

namespace App\Filament\Resources\SerpAnalysisResults\Pages;

use App\Filament\Resources\SerpAnalysisResults\SerpAnalysisResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSerpAnalysisResults extends ListRecords
{
    protected static string $resource = SerpAnalysisResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
