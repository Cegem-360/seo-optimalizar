<?php

namespace App\Filament\Resources\PageSpeedResults\Pages;

use App\Filament\Resources\PageSpeedResults\PageSpeedResultResource;
use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePageSpeedResult extends CreateRecord
{
    protected static string $resource = PageSpeedResultResource::class;

    protected static ?string $title = 'Run PageSpeed Analysis';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'PageSpeed analysis started';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Run Analysis');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Get the current tenant (project)
        $project = Filament::getTenant();

        if ($project instanceof Project) {
            // Run PageSpeed analysis for this project
            try {
                $manager = ApiServiceManager::forProject($project);
                $pageSpeed = $manager->getPageSpeedInsights();

                if (! $pageSpeed->isConfigured()) {
                    Notification::make()
                        ->title('PageSpeed API not configured')
                        ->body('Please configure PageSpeed Insights API for this project.')
                        ->danger()
                        ->send();

                    $this->halt();
                }

                // Analyze with the selected strategy
                $strategy = $data['strategy'] ?? 'mobile';
                $pageSpeed->analyzeProjectUrl($strategy);

                // The PageSpeed service already saves the results to database
                // So we need to cancel the creation here to avoid duplicate
                Notification::make()
                    ->title('PageSpeed Analysis Completed')
                    ->body(sprintf('Successfully analyzed %s with %s strategy.', $project->name, $strategy))
                    ->success()
                    ->send();

                // Redirect to the list page instead of creating a duplicate
                $this->redirect($this->getResource()::getUrl('index'));
                $this->halt();
            } catch (Exception $e) {
                Notification::make()
                    ->title('PageSpeed Analysis Failed')
                    ->body('Error: ' . $e->getMessage())
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
