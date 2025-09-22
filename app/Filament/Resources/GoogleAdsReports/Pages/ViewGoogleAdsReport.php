<?php

namespace App\Filament\Resources\GoogleAdsReports\Pages;

use App\Filament\Resources\GoogleAdsReports\GoogleAdsReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGoogleAdsReport extends ViewRecord
{
    protected static string $resource = GoogleAdsReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
