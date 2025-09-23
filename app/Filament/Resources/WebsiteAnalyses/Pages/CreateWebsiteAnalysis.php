<?php

declare(strict_types=1);

namespace App\Filament\Resources\WebsiteAnalyses\Pages;

use App\Filament\Resources\WebsiteAnalyses\WebsiteAnalysisResource;
use App\Models\Project;
use App\Models\WebsiteAnalysis;
use App\Services\WebsiteAnalysisService;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWebsiteAnalysis extends CreateRecord
{
    protected static string $resource = WebsiteAnalysisResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Elemzés létrehozása');
    }

    protected function getCreateAndAnalyzeAction(): Action
    {
        return Action::make('createAndAnalyze')
            ->label('Létrehozás és elemzés indítása')
            ->action(function () {
                $this->create();

                if ($this->record instanceof Model) {
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
            /** @var Project $project */
            $project = $tenant;
            $record->update(['project_id' => $project->id]);
        }
    }

    private function runAnalysis(WebsiteAnalysis $websiteAnalysis): void
    {
        try {
            $service = app(WebsiteAnalysisService::class);
            $service->runAiAnalysis($websiteAnalysis);

            Notification::make()
                ->title('Elemzés sikeresen elkészült!')
                ->success()
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->title('Hiba történt az elemzés során')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
