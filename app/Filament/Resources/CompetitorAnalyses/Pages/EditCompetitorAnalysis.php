<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetitorAnalyses\Pages;

use App\Filament\Resources\CompetitorAnalyses\CompetitorAnalysisResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompetitorAnalysis extends EditRecord
{
    protected static string $resource = CompetitorAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
