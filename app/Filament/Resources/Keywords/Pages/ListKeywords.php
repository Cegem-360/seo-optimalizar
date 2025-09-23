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
use Illuminate\Support\Facades\Artisan;

class ListKeywords extends ListRecords
{
    protected static string $resource = KeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_keywords')
                ->label('Kulcsszavak importálása')
                ->icon(Heroicon::CloudArrowDown)
                ->color('success')
                ->modalHeading('Kulcsszavak importálása')
                ->modalDescription('Importálja az új kulcsszavakat a Google Search Console-ból az elmúlt 30 napból.')
                ->modalSubmitActionLabel('Importálás')
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
                        $importedCount = $gscService->importKeywords();

                        Notification::make()
                            ->title('Importálás sikeres')
                            ->body("Sikeresen importáltunk {$importedCount} kulcsszót.")
                            ->success()
                            ->send();

                        $this->refreshTable();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Importálási hiba')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('update_keywords')
                ->label('Metrikák frissítése')
                ->icon(Heroicon::ArrowPath)
                ->color('warning')
                ->modalHeading('Kulcsszavak metrikáinak frissítése')
                ->modalDescription('Frissíti a meglévő kulcsszavak metrikáit (search volume, difficulty) a Google Ads API-ból.')
                ->modalSubmitActionLabel('Frissítés')
                ->action(function (): void {
                    try {
                        $project = Filament::getTenant();

                        if (! $project) {
                            throw new Exception('Projekt nem található');
                        }

                        $exitCode = Artisan::call('seo:update-keywords', [
                            'project' => $project->id,
                            '--batch-size' => 10,
                        ]);
                        $output = Artisan::output();

                        if ($exitCode === 0) {
                            Notification::make()
                                ->title('Frissítés sikeres')
                                ->body('A kulcsszavak metrikái sikeresen frissítve lettek.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Frissítési figyelmeztetés')
                                ->body('A frissítés során problémák léptek fel.')
                                ->warning()
                                ->send();
                        }

                        $this->refreshTable();
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Frissítési hiba')
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
