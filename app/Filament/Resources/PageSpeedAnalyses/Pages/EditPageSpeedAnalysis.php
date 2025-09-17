<?php

namespace App\Filament\Resources\PageSpeedAnalyses\Pages;

use App\Filament\Resources\PageSpeedAnalyses\PageSpeedAnalysisResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPageSpeedAnalysis extends EditRecord
{
    protected static string $resource = PageSpeedAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
