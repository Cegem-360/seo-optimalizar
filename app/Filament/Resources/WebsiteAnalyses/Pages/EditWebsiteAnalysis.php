<?php

namespace App\Filament\Resources\WebsiteAnalyses\Pages;

use App\Filament\Resources\WebsiteAnalyses\WebsiteAnalysisResource;
use App\Models\WebsiteAnalysis;
use App\Services\WebsiteAnalysisService;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteAnalysis extends EditRecord
{
    protected static string $resource = WebsiteAnalysisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reanalyze')
                ->label('Újraelemzés')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Elemzés újrafuttatása')
                ->modalDescription('Biztosan újra szeretnéd futtatni az elemzést? Ez felülírja a jelenlegi eredményeket.')
                ->modalSubmitActionLabel('Igen, újraelemzés')
                ->action(function () {
                    $this->runReanalysis();
                }),

            Actions\Action::make('runAiAnalysis')
                ->label('AI elemzés indítása')
                ->icon('heroicon-m-play')
                ->color('success')
                ->visible(fn () => $this->record instanceof WebsiteAnalysis && $this->record->status === 'pending')
                ->action(function () {
                    $this->runAiAnalysis();
                }),

            DeleteAction::make(),
        ];
    }

    private function runReanalysis(): void
    {
        try {
            $service = app(WebsiteAnalysisService::class);

            /** @var WebsiteAnalysis $record */
            $record = $this->record;

            // Státusz visszaállítása
            $record->update([
                'status' => 'processing',
                'analyzed_at' => null,
                'overall_score' => null,
                'scores' => null,
                'metadata' => null,
                'raw_response' => null,
                'error_message' => null,
            ]);

            // Szakaszok törlése
            $record->sections()->delete();

            // Új elemzés futtatása
            $demoResponse = $service->getDemoResponse($record->analysis_type);
            $service->processAiResponse($record, $demoResponse);

            // Frissített adatok betöltése
            $this->refreshFormData([
                'status',
                'analyzed_at',
                'overall_score',
                'scores',
                'metadata',
                'raw_response',
            ]);

            Notification::make()
                ->title('Újraelemzés sikeresen elkészült!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hiba történt az újraelemzés során')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function runAiAnalysis(): void
    {
        try {
            $service = app(WebsiteAnalysisService::class);

            /** @var WebsiteAnalysis $record */
            $record = $this->record;

            $record->update(['status' => 'processing']);

            $demoResponse = $service->getDemoResponse($record->analysis_type);
            $service->processAiResponse($record, $demoResponse);

            $this->refreshFormData([
                'status',
                'analyzed_at',
                'overall_score',
                'scores',
                'metadata',
                'raw_response',
            ]);

            Notification::make()
                ->title('AI elemzés sikeresen elkészült!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hiba történt az AI elemzés során')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
