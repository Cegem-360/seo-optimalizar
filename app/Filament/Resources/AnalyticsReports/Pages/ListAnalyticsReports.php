<?php

namespace App\Filament\Resources\AnalyticsReports\Pages;

use App\Filament\Resources\AnalyticsReports\AnalyticsReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnalyticsReports extends ListRecords
{
    protected static string $resource = AnalyticsReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
