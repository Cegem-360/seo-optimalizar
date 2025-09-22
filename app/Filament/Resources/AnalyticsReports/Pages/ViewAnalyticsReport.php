<?php

namespace App\Filament\Resources\AnalyticsReports\Pages;

use App\Filament\Resources\AnalyticsReports\AnalyticsReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAnalyticsReport extends ViewRecord
{
    protected static string $resource = AnalyticsReportResource::class;

    protected function getActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}