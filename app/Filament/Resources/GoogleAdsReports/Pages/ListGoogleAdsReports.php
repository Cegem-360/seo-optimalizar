<?php

namespace App\Filament\Resources\GoogleAdsReports\Pages;

use App\Filament\Resources\GoogleAdsReports\GoogleAdsReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGoogleAdsReports extends ListRecords
{
    protected static string $resource = GoogleAdsReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
