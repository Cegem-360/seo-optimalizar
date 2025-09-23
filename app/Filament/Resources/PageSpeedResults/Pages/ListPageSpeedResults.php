<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageSpeedResults\Pages;

use App\Filament\Resources\PageSpeedResults\PageSpeedResultResource;
use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPageSpeedResults extends ListRecords
{
    protected static string $resource = PageSpeedResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dashboard')
                ->label('View Dashboard')
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('dashboard')),
            Action::make('runAnalysis')
                ->label('Run New Analysis')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Run PageSpeed Analysis')
                ->modalDescription('Choose the analysis strategy for your site.')
                ->modalSubmitActionLabel('Run Analysis')
                ->schema([
                    Select::make('strategy')
                        ->label('Analysis Strategy')
                        ->options([
                            'mobile' => 'Mobile',
                            'desktop' => 'Desktop',
                        ])
                        ->default('mobile')
                        ->required()
                        ->helperText('Choose whether to analyze the mobile or desktop version of your site'),
                ])
                ->action(function (array $data): void {
                    $project = Filament::getTenant();

                    if ($project instanceof Project) {
                        try {
                            $manager = ApiServiceManager::forProject($project);
                            $pageSpeed = $manager->getPageSpeedInsights();

                            if (! $pageSpeed->isConfigured()) {
                                Notification::make()
                                    ->title('PageSpeed API not configured')
                                    ->body('Please configure PageSpeed Insights API for this project.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $strategy = $data['strategy'] ?? 'mobile';
                            $pageSpeed->analyzeProjectUrl($strategy);

                            Notification::make()
                                ->title('PageSpeed Analysis Completed')
                                ->body(sprintf('Successfully analyzed %s with %s strategy.', $project->name, $strategy))
                                ->success()
                                ->send();

                            $this->redirect($this->getUrl());
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('PageSpeed Analysis Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }
                }),
        ];
    }
}
