<?php

namespace App\Filament\Resources\SeoAnalyses\Pages;

use App\Filament\Resources\SeoAnalyses\SeoAnalysisResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeoAnalysis extends EditRecord
{
    protected static string $resource = SeoAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
