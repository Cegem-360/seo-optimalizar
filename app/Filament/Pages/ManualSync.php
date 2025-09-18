<?php

namespace App\Filament\Pages;

use App\Models\Project;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class ManualSync extends Page
{
    protected string $view = 'filament.pages.manual-sync';

    protected static BackedEnum|string|null $navigationIcon = Heroicon::ArrowPath;

    protected static ?string $navigationLabel = 'Manual Sync';

    protected static ?string $title = 'Manual Data Synchronization';

    protected static ?int $navigationSort = 100;

    public bool $isLoading = false;

    public array $syncResults = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testApiConnections')
                ->label('Test API Connections')
                ->icon(Heroicon::Wifi)
                ->color('info')
                ->action(function (): void {
                    $this->testApiConnections();
                }),

            Action::make('syncSearchConsole')
                ->label('Sync Search Console Data')
                ->icon(Heroicon::MagnifyingGlass)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This will import keyword rankings and performance data from Google Search Console.')
                ->action(function (): void {
                    $this->syncSearchConsoleData();
                }),

            Action::make('updateKeywords')
                ->label('Update Keyword Metrics')
                ->icon(Heroicon::Key)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This will update search volume and difficulty scores for keywords using Google Ads API.')
                ->action(function (): void {
                    $this->updateKeywordMetrics();
                }),

            Action::make('updateKeywordsHistorical')
                ->label('Update Historical Metrics')
                ->icon(Heroicon::ChartBar)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('This will update detailed historical metrics for keywords. This uses more API credits!')
                ->action(function (): void {
                    $this->updateHistoricalMetrics();
                }),

            Action::make('analyzePageSpeed')
                ->label('Analyze Page Speed')
                ->icon(Heroicon::Bolt)
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription('This will run PageSpeed analysis for the current project.')
                ->action(function (): void {
                    $this->analyzePageSpeed();
                }),

            Action::make('clearResults')
                ->label('Clear Results')
                ->icon(Heroicon::Trash)
                ->color('gray')
                ->action(function (): void {
                    $this->syncResults = [];
                    $this->isLoading = false;
                }),
        ];
    }

    protected function testApiConnections(): void
    {
        $this->isLoading = true;
        $this->syncResults = [];

        try {
            $tenant = Filament::getTenant();
            if (! $tenant instanceof Project) {
                throw new Exception('No project selected');
            }

            $exitCode = Artisan::call('seo:test-api', ['project' => $tenant->id]);
            $output = Artisan::output();

            $this->syncResults[] = [
                'operation' => 'API Connection Test',
                'status' => $exitCode === 0 ? 'success' : 'error',
                'message' => 'API connections tested',
                'output' => $output,
                'timestamp' => now()->format('H:i:s'),
            ];

            if ($exitCode === 0) {
                Notification::make()
                    ->title('API Connections Tested')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('API Connection Issues Found')
                    ->warning()
                    ->send();
            }
        } catch (Exception $exception) {
            $this->syncResults[] = [
                'operation' => 'API Connection Test',
                'status' => 'error',
                'message' => $exception->getMessage(),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            Notification::make()
                ->title('Error Testing API Connections')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function syncSearchConsoleData(): void
    {
        $this->isLoading = true;

        try {
            $tenant = Filament::getTenant();
            if (! $tenant instanceof Project) {
                throw new Exception('No project selected');
            }

            $exitCode = Artisan::call('seo:check-positions', ['--project' => $tenant->id]);
            $output = Artisan::output();

            $this->syncResults[] = [
                'operation' => 'Search Console Sync',
                'status' => $exitCode === 0 ? 'success' : 'error',
                'message' => 'Search Console data sync completed',
                'output' => $output,
                'timestamp' => now()->format('H:i:s'),
            ];

            if ($exitCode === 0) {
                Notification::make()
                    ->title('Search Console Data Synced')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Search Console Sync Issues')
                    ->warning()
                    ->send();
            }
        } catch (Exception $exception) {
            $this->syncResults[] = [
                'operation' => 'Search Console Sync',
                'status' => 'error',
                'message' => $exception->getMessage(),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            Notification::make()
                ->title('Error Syncing Search Console')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function updateKeywordMetrics(): void
    {
        $this->isLoading = true;

        try {
            $tenant = Filament::getTenant();
            if (! $tenant instanceof Project) {
                throw new Exception('No project selected');
            }

            $exitCode = Artisan::call('seo:update-keywords', [
                'project' => $tenant->id,
                '--batch-size' => 10,
            ]);
            $output = Artisan::output();

            $this->syncResults[] = [
                'operation' => 'Keyword Metrics Update',
                'status' => $exitCode === 0 ? 'success' : 'error',
                'message' => 'Keyword metrics updated',
                'output' => $output,
                'timestamp' => now()->format('H:i:s'),
            ];

            if ($exitCode === 0) {
                Notification::make()
                    ->title('Keyword Metrics Updated')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Keyword Update Issues')
                    ->warning()
                    ->send();
            }
        } catch (Exception $exception) {
            $this->syncResults[] = [
                'operation' => 'Keyword Metrics Update',
                'status' => 'error',
                'message' => $exception->getMessage(),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            Notification::make()
                ->title('Error Updating Keywords')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function updateHistoricalMetrics(): void
    {
        $this->isLoading = true;

        try {
            $tenant = Filament::getTenant();
            if (! $tenant instanceof Project) {
                throw new Exception('No project selected');
            }

            $exitCode = Artisan::call('seo:update-keywords-historical', [
                'project' => $tenant->id,
                '--batch-size' => 3,
                '--force' => true,
            ]);
            $output = Artisan::output();

            $this->syncResults[] = [
                'operation' => 'Historical Metrics Update',
                'status' => $exitCode === 0 ? 'success' : 'error',
                'message' => 'Historical metrics updated',
                'output' => $output,
                'timestamp' => now()->format('H:i:s'),
            ];

            if ($exitCode === 0) {
                Notification::make()
                    ->title('Historical Metrics Updated')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Historical Metrics Update Issues')
                    ->warning()
                    ->send();
            }
        } catch (Exception $exception) {
            $this->syncResults[] = [
                'operation' => 'Historical Metrics Update',
                'status' => 'error',
                'message' => $exception->getMessage(),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            Notification::make()
                ->title('Error Updating Historical Metrics')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function analyzePageSpeed(): void
    {
        $this->isLoading = true;

        try {
            $tenant = Filament::getTenant();
            if (! $tenant instanceof Project) {
                throw new Exception('No project selected');
            }

            $exitCode = Artisan::call('seo:pagespeed', ['project' => $tenant->id]);
            $output = Artisan::output();

            $this->syncResults[] = [
                'operation' => 'PageSpeed Analysis',
                'status' => $exitCode === 0 ? 'success' : 'error',
                'message' => 'PageSpeed analysis completed',
                'output' => $output,
                'timestamp' => now()->format('H:i:s'),
            ];

            if ($exitCode === 0) {
                Notification::make()
                    ->title('PageSpeed Analysis Completed')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('PageSpeed Analysis Issues')
                    ->warning()
                    ->send();
            }
        } catch (Exception $exception) {
            $this->syncResults[] = [
                'operation' => 'PageSpeed Analysis',
                'status' => 'error',
                'message' => $exception->getMessage(),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            Notification::make()
                ->title('Error Running PageSpeed Analysis')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }
}
