<?php

declare(strict_types=1);

namespace App\Filament\Resources\GoogleAdsReports\Pages;

use App\Filament\Resources\GoogleAdsReports\GoogleAdsReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGoogleAdsReport extends CreateRecord
{
    protected static string $resource = GoogleAdsReportResource::class;
}
