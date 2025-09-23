<?php

declare(strict_types=1);

namespace App\Filament\Resources\Keywords\Pages;

use App\Filament\Resources\Keywords\KeywordResource;
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
            Action::make('sync_keywords')
                ->label('Kulcsszavak frissítése')
                ->icon(Heroicon::ArrowPath)
                ->color('primary')
                ->modalHeading('Kulcsszavak frissítése')
                ->modalDescription('Frissíti a kulcsszavak listáját a projekt alapján.')
                ->modalSubmitActionLabel('Frissítés')
                ->action(function () {
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
