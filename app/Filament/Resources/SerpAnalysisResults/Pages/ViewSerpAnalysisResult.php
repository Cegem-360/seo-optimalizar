<?php

declare(strict_types=1);

namespace App\Filament\Resources\SerpAnalysisResults\Pages;

use App\Filament\Resources\SerpAnalysisResults\SerpAnalysisResultResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSerpAnalysisResult extends ViewRecord
{
    protected static string $resource = SerpAnalysisResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
