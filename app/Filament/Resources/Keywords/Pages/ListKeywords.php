<?php

declare(strict_types=1);

namespace App\Filament\Resources\Keywords\Pages;

use App\Filament\Resources\Keywords\KeywordResource;
use App\Services\Api\ApiServiceManager;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListKeywords extends ListRecords
{
    protected static string $resource = KeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_keywords')
                ->label('Search Console szinkronizálás')
                ->icon(Heroicon::ArrowPath)
                ->color('warning')
                ->modalHeading('Kulcsszavak szinkronizálása')
                ->modalDescription('Frissíti a kiemelt prioritású kulcsszavak pozícióit és teljesítményadatait a Google Search Console-ból.')
                ->modalSubmitActionLabel('Szinkronizálás')
                ->action(function () {
                    try {
                        $project = Filament::getTenant();

                        if (! $project) {
                            throw new Exception('Projekt nem található');
                        }

                        $apiManager = new ApiServiceManager($project);

                        if (! $apiManager->hasService('google_search_console')) {
                            throw new Exception('Google Search Console nincs konfigurálva ehhez a projekthez');
                        }

                        $gscService = $apiManager->getGoogleSearchConsole();
                        $syncedCount = $gscService->syncKeywordRankings();

                        Notification::make()
                            ->title('Szinkronizálás sikeres')
                            ->body("Sikeresen szinkronizáltunk {$syncedCount} keresési eredményt.")
                            ->success()
                            ->send();

                        $this->refreshTable();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Szinkronizálási hiba')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }

    private function refreshTable(): void
    {
        $this->dispatch('refresh');
    }
}
