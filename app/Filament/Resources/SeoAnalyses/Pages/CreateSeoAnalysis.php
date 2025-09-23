<?php

declare(strict_types=1);

namespace App\Filament\Resources\SeoAnalyses\Pages;

use App\Filament\Resources\SeoAnalyses\SeoAnalysisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeoAnalysis extends CreateRecord
{
    protected static string $resource = SeoAnalysisResource::class;
}
