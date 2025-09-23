<?php

declare(strict_types=1);

namespace App\Filament\Resources\GoogleAdsReports\Pages;

use App\Filament\Resources\GoogleAdsReports\GoogleAdsReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGoogleAdsReport extends EditRecord
{
    protected static string $resource = GoogleAdsReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
