<?php

namespace App\Filament\Resources\WebsiteAnalyses\Pages;

use App\Filament\Resources\WebsiteAnalyses\WebsiteAnalysisResource;
use App\Models\WebsiteAnalysis;
use App\Services\WebsiteAnalysisService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateWebsiteAnalysis extends CreateRecord
{
    protected static string $resource = WebsiteAnalysisResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Elemzés létrehozása');
    }

    protected function getCreateAndAnalyzeAction(): Actions\Action
    {
        return Actions\Action::make('createAndAnalyze')
            ->label('Létrehozás és elemzés indítása')
            ->action(function () {
                $this->create();

                if ($this->record) {
                    /** @var WebsiteAnalysis $record */
                    $record = $this->record;
                    $this->runAnalysis($record);

                    return redirect($this->getResource()::getUrl('edit', ['record' => $record]));
                }
            })
            ->color('success')
            ->icon('heroicon-m-play');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAndAnalyzeAction(),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterCreate(): void
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            /** @var WebsiteAnalysis $record */
            $record = $this->record;
            /** @var \App\Models\Project $project */
            $project = $tenant;
            $record->update(['project_id' => $project->id]);
        }
    }

    private function runAnalysis(WebsiteAnalysis $record): void
    {
        try {
            $service = app(WebsiteAnalysisService::class);

            // Dummy AI válasz a demo céljából
            $demoResponse = $service->getDemoResponse($record->analysis_type);

            $service->processAiResponse($record, $demoResponse);

            Notification::make()
                ->title('Elemzés sikeresen elkészült!')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Hiba történt az elemzés során')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
