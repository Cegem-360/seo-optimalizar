<?php

declare(strict_types=1);

namespace App\Filament\Resources\AnalyticsReports\Pages;

use App\Filament\Resources\AnalyticsReports\AnalyticsReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnalyticsReport extends CreateRecord
{
    protected static string $resource = AnalyticsReportResource::class;
}
