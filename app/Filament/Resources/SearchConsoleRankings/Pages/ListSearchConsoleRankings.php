<?php

declare(strict_types=1);

namespace App\Filament\Resources\SearchConsoleRankings\Pages;

use App\Filament\Resources\SearchConsoleRankings\SearchConsoleRankingResource;
use App\Services\Api\ApiServiceManager;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSearchConsoleRankings extends ListRecords
{
    protected static string $resource = SearchConsoleRankingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_search_console')
                ->label('Search Console szinkronizálás')
                ->icon(Heroicon::ArrowUpTray)
                ->color('primary')
                ->modalHeading('Search Console adatok szinkronizálása')
                ->modalDescription('Frissíti a keresési pozíciókat és teljesítményadatokat a Google Search Console-ból.')
                ->modalSubmitActionLabel('Szinkronizálás')
                ->action(function (): void {
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
