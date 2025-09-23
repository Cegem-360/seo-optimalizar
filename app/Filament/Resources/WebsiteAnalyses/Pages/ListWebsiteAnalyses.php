<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebsiteAnalyses\Pages;

use App\Filament\Resources\WebsiteAnalyses\WebsiteAnalysisResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteAnalyses extends ListRecords
{
    protected static string $resource = WebsiteAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
