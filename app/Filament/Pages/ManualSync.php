<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Api\GoogleAdsApiService;
use App\Services\GoogleAdsService;
use BackedEnum;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
            Action::make('syncSearchConsole')
                ->label('Sync Search Console')
                ->icon(Heroicon::MagnifyingGlass)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This will import keyword rankings and performance data from Google Search Console.')
                ->action(function (): void {
                    $this->syncSearchConsoleData();
                }),

            Action::make('syncGoogleAnalytics')
                ->label('Sync Analytics (7 days)')
                ->icon(Heroicon::ChartBarSquare)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sync Google Analytics Data')
                ->modalDescription('This will fetch Google Analytics data for the last 7 days, collecting data day by day.')
                ->action(function (): void {
                    $this->syncGoogleAnalytics(7);
                }),

            ActionGroup::make([
                Action::make('syncGoogleAnalytics30')
                    ->label('Sync Analytics (30 days)')
                    ->icon(Heroicon::ChartBarSquare)
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Google Analytics Data - 30 Days')
                    ->modalDescription('This will fetch Google Analytics data for the last 30 days. This may take several minutes to complete.')
                    ->action(function (): void {
                        $this->syncGoogleAnalytics(30);
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

                Action::make('syncGoogleAdsData')
                    ->label('Sync Google Ads Data')
                    ->icon(Heroicon::CurrencyDollar)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Google Ads Data')
                    ->modalDescription('This will fetch Google Ads keyword data and save it as a report for analysis.')
                    ->action(function (): void {
                        $this->syncGoogleAdsData();
                    }),

                Action::make('testApiConnections')
                    ->label('Test API Connections')
                    ->icon(Heroicon::Wifi)
                    ->color('info')
                    ->action(function (): void {
                        $this->testApiConnections();
                    }),
            ])
                ->label('More Actions')
                ->icon(Heroicon::EllipsisVertical)
                ->button()
                ->color('gray'),

            Action::make('clearResults')
                ->label('Clear')
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

    protected function syncGoogleAnalytics(int $days = 7): void
    {
        // Increase execution time limit for this operation
        set_time_limit(600); // 10 minutes

        $this->isLoading = true;

        try {
            $tenant = Filament::getTenant();
            if (! $tenant instanceof Project) {
                throw new Exception('No project selected');
            }

            $this->syncResults[] = [
                'operation' => 'Google Analytics Sync',
                'status' => 'info',
                'message' => sprintf('Starting Google Analytics data collection for last %d days...', $days),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            $successCount = 0;
            $errorCount = 0;

            // Collect data for the specified number of days
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');

                try {
                    $exitCode = Artisan::call('analytics:collect-daily', [
                        '--project' => $tenant->id,
                        '--date' => $date,
                    ]);

                    if ($exitCode === 0) {
                        $successCount++;
                        $this->syncResults[] = [
                            'operation' => 'Analytics Collection',
                            'status' => 'success',
                            'message' => 'Successfully collected data for ' . $date,
                            'output' => '',
                            'timestamp' => now()->format('H:i:s'),
                        ];
                    } else {
                        $errorCount++;
                        $this->syncResults[] = [
                            'operation' => 'Analytics Collection',
                            'status' => 'warning',
                            'message' => 'Issues collecting data for ' . $date,
                            'output' => Artisan::output(),
                            'timestamp' => now()->format('H:i:s'),
                        ];
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $this->syncResults[] = [
                        'operation' => 'Analytics Collection',
                        'status' => 'error',
                        'message' => sprintf('Error collecting data for %s: %s', $date, $e->getMessage()),
                        'output' => '',
                        'timestamp' => now()->format('H:i:s'),
                    ];
                }

                // Add a small delay to avoid rate limiting (reduced from 1 second)
                usleep(200000); // 0.2 seconds
            }

            $this->syncResults[] = [
                'operation' => 'Google Analytics Sync',
                'status' => $errorCount === 0 ? 'success' : ($successCount > 0 ? 'warning' : 'error'),
                'message' => sprintf('Analytics sync completed. Success: %d, Errors: %d', $successCount, $errorCount),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            if ($errorCount === 0) {
                Notification::make()
                    ->title('Google Analytics Data Synced')
                    ->body(sprintf('Successfully collected data for all %d days', $days))
                    ->success()
                    ->send();
            } elseif ($successCount > 0) {
                Notification::make()
                    ->title('Google Analytics Sync Partial Success')
                    ->body(sprintf('Collected data for %d days, %d failed', $successCount, $errorCount))
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Google Analytics Sync Failed')
                    ->body(sprintf('Failed to collect data for the requested %d days', $days))
                    ->danger()
                    ->send();
            }
        } catch (Exception $exception) {
            $this->syncResults[] = [
                'operation' => 'Google Analytics Sync',
                'status' => 'error',
                'message' => $exception->getMessage(),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            Notification::make()
                ->title('Error Syncing Google Analytics')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function syncGoogleAdsData(): void
    {
        $this->isLoading = true;

        try {
            $tenant = Filament::getTenant();
            if (! $tenant instanceof Project) {
                throw new Exception('No project selected');
            }

            $this->syncResults[] = [
                'operation' => 'Google Ads Data Sync',
                'status' => 'info',
                'message' => 'Starting Google Ads data collection...',
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            // Initialize Google Ads service
            $googleAdsApiService = new GoogleAdsApiService($tenant);
            $googleAdsReportService = app(GoogleAdsService::class);

            // Test connection
            $connectionStatus = $googleAdsApiService->testConnection();

            if (! $connectionStatus) {
                $this->syncResults[] = [
                    'operation' => 'Google Ads Data Sync',
                    'status' => 'warning',
                    'message' => 'Google Ads API not configured, using project keywords for mock data',
                    'output' => '',
                    'timestamp' => now()->format('H:i:s'),
                ];
            }

            // Get keywords from project (up to 10 for rate limiting)
            $keywords = $tenant->keywords()
                ->inRandomOrder()
                ->limit(10)
                ->pluck('keyword')
                ->toArray();

            if (empty($keywords)) {
                $keywords = ['seo', 'marketing', 'google ads'];
            }

            $debugData = [
                'metadata' => [
                    'project' => $tenant->name,
                    'service' => 'google_ads',
                    'fetched_at' => now()->toIso8601String(),
                    'connection_status' => $connectionStatus,
                    'actual_status' => $connectionStatus,
                    'keyword_source' => 'project_keywords',
                    'keyword_limit' => count($keywords),
                ],
            ];

            $keywordData = [];
            $historicalData = [];
            $bulkData = [];
            $successCount = 0;

            if ($connectionStatus) {
                // Real API calls
                foreach ($keywords as $keyword) {
                    try {
                        // Get regular keyword data
                        $kwData = $googleAdsApiService->getKeywordData($keyword, 'HU');
                        if ($kwData !== null && $kwData !== []) {
                            $keywordData[$keyword] = $kwData;
                            $successCount++;
                        }

                        // Get historical metrics
                        $histData = $googleAdsApiService->getHistoricalMetrics($keyword, 'HU');
                        if ($histData !== null && $histData !== []) {
                            $historicalData[$keyword] = $histData;
                        }
                    } catch (Exception $e) {
                        $this->syncResults[] = [
                            'operation' => 'Google Ads Data Sync',
                            'status' => 'warning',
                            'message' => 'Failed to fetch data for keyword: ' . $keyword,
                            'output' => $e->getMessage(),
                            'timestamp' => now()->format('H:i:s'),
                        ];
                    }
                }

                // Bulk operation
                $bulkResults = $googleAdsApiService->bulkGetKeywordData(collect($keywords), 'HU');
                $bulkData = $bulkResults;
            } else {
                // Mock data
                foreach ($keywords as $keyword) {
                    $keywordData[$keyword] = [
                        'keyword' => $keyword,
                        'search_volume' => random_int(1000, 50000),
                        'competition' => round(random_int(10, 90) / 100, 2),
                        'low_bid' => round(random_int(50, 300) / 100, 2),
                        'high_bid' => round(random_int(300, 1000) / 100, 2),
                        'difficulty' => random_int(20, 85),
                    ];

                    $monthlyVolumes = [];
                    for ($i = 11; $i >= 0; $i--) {
                        $date = now()->subMonths($i);
                        $monthlyVolumes[] = [
                            'year' => $date->year,
                            'month' => $date->month,
                            'monthly_searches' => random_int(800, 60000),
                        ];
                    }

                    $historicalData[$keyword] = [
                        'keyword' => $keyword,
                        'avg_monthly_searches' => random_int(1000, 50000),
                        'competition' => round(random_int(10, 90) / 100, 2),
                        'competition_index' => random_int(20, 80),
                        'monthly_search_volumes' => $monthlyVolumes,
                    ];

                    $successCount++;
                }

                $bulkData = $keywordData;
            }

            $debugData['keyword_data'] = $keywordData;
            $debugData['historical_metrics'] = $historicalData;
            $debugData['bulk_results'] = $bulkData;
            $debugData['statistics'] = [
                'keywords_tested' => count($keywords),
                'successful_fetches' => $successCount,
                'historical_fetches' => count($historicalData),
                'bulk_fetches' => count($bulkData),
            ];

            // Save to database
            $report = $googleAdsReportService->storeGoogleAdsReport($tenant, $debugData, Carbon::today());

            $this->syncResults[] = [
                'operation' => 'Google Ads Data Sync',
                'status' => 'success',
                'message' => sprintf('Google Ads report saved with %d keywords', $successCount),
                'output' => 'Report ID: ' . $report->id,
                'timestamp' => now()->format('H:i:s'),
            ];

            Notification::make()
                ->title('Google Ads Data Synced')
                ->body(sprintf('Successfully saved report with %d keywords', $successCount))
                ->success()
                ->send();
        } catch (Exception $exception) {
            $this->syncResults[] = [
                'operation' => 'Google Ads Data Sync',
                'status' => 'error',
                'message' => $exception->getMessage(),
                'output' => '',
                'timestamp' => now()->format('H:i:s'),
            ];

            Notification::make()
                ->title('Error Syncing Google Ads Data')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }
}
