<?php

namespace App\Filament\Resources\WebsiteAnalyses\Pages;

use App\Filament\Resources\WebsiteAnalyses\WebsiteAnalysisResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteAnalysis extends EditRecord
{
    protected static string $resource = WebsiteAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
