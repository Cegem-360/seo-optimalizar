<?php

declare(strict_types=1);

namespace App\Filament\Resources\SearchConsoleRankings\Pages;

use App\Filament\Resources\SearchConsoleRankings\SearchConsoleRankingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSearchConsoleRanking extends EditRecord
{
    protected static string $resource = SearchConsoleRankingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
