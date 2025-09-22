<?php

namespace App\Filament\Pages;

use App\Models\ApiCredential;
use App\Models\Keyword;
use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use App\Services\Api\GoogleAdsApiService;
use App\Services\Api\GoogleAnalytics4Service;
use App\Services\GoogleSearchConsoleService;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Google\ApiCore\ApiException;
use Google\Client as GoogleClient;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use UnitEnum;

class ApiDataDebugger extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bug-ant';

    protected string $view = 'filament.pages.api-data-debugger';

    protected static ?string $navigationLabel = 'API Data Debugger';

    protected static ?string $title = 'API Raw Data Viewer';

    protected static string|UnitEnum|null $navigationGroup = 'System Tools';

    protected static ?int $navigationSort = 99;

    public ?array $searchConsoleData = null;

    public ?array $analyticsData = null;

    public ?array $googleAdsData = null;

    public ?string $selectedService = 'search_console';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fetchSearchConsole')
                ->label('Fetch Search Console Data')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->fillForm([
                    'startDate' => Carbon::now()->subDays(7),
                    'endDate' => Carbon::now(),
                    'limit' => 50,
                ])
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->required(),
                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->required(),
                    TextInput::make('limit')
                        ->label('Row Limit')
                        ->numeric()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->fetchSearchConsoleData($data);
                }),

            Action::make('fetchAnalytics')
                ->label('Fetch Analytics Data')
                ->icon('heroicon-o-chart-bar')
                ->color('success')
                ->fillForm([
                    'startDate' => Carbon::now()->subDays(7),
                    'endDate' => Carbon::now(),
                ])
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->required(),
                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->fetchAnalyticsData($data);
                }),

            Action::make('fetchGoogleAds')
                ->label('Fetch Google Ads Data')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->fillForm([
                    'startDate' => Carbon::now()->subDays(7),
                    'endDate' => Carbon::now(),
                    'limit' => 10,
                    'use_project_keywords' => true,
                ])
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Start Date'),
                    DatePicker::make('endDate')
                        ->label('End Date'),
                    Select::make('use_project_keywords')
                        ->label('Data Source')
                        ->options([
                            true => 'Use Project Keywords',
                            false => 'Use Custom Keywords',
                        ])
                        ->default(true)
                        ->reactive(),
                    Select::make('keywords')
                        ->label('Select Keywords from Project')
                        ->options(function () {
                            $project = Filament::getTenant();
                            if (!$project instanceof Project) {
                                return [];
                            }
                            return $project->keywords()
                                ->pluck('keyword', 'keyword')
                                ->toArray();
                        })
                        ->multiple()
                        ->searchable()
                        ->visible(fn ($get) => $get('use_project_keywords') === true)
                        ->helperText('Select keywords from your project to test (max 10 recommended)'),
                    TextInput::make('custom_keywords')
                        ->label('Custom Keywords')
                        ->placeholder('Enter keywords separated by commas (e.g. seo, marketing, ads)')
                        ->visible(fn ($get) => $get('use_project_keywords') === false)
                        ->helperText('Enter custom keywords separated by commas'),
                    TextInput::make('limit')
                        ->label('Keyword Limit')
                        ->numeric()
                        ->default(10)
                        ->minValue(1)
                        ->maxValue(20)
                        ->helperText('Maximum number of keywords to test (to avoid rate limits)'),
                ])
                ->action(function (array $data): void {
                    $this->fetchGoogleAdsData($data);
                }),
        ];
    }

    private function fetchSearchConsoleData(array $data): void
    {
        try {
            $this->errorMessage = null;
            $project = Filament::getTenant();

            if (! $project instanceof Project) {
                throw new Exception('No project selected');
            }

            $searchConsoleService = app(GoogleSearchConsoleService::class);

            if (! $searchConsoleService->hasCredentials()) {
                throw new Exception('Google Search Console credentials not configured');
            }

            // Direct API call to get raw data
            $googleClient = new GoogleClient();
            $googleClient->setApplicationName('SEO Monitor Debug');
            $googleClient->setScopes([SearchConsole::WEBMASTERS_READONLY]);

            $credentialsPath = config('services.google.credentials_path');
            if ($credentialsPath) {
                $fullPath = base_path($credentialsPath);
                if (file_exists($fullPath)) {
                    $googleClient->setAuthConfig($fullPath);
                    $googleClient->useApplicationDefaultCredentials();

                    if ($subject = config('services.google.workspace_subject')) {
                        $googleClient->setSubject($subject);
                    }
                }
            }

            $searchConsole = new SearchConsole($googleClient);

            $searchAnalyticsQueryRequest = new SearchAnalyticsQueryRequest();
            $searchAnalyticsQueryRequest->setStartDate(Carbon::parse($data['startDate'])->format('Y-m-d'));
            $searchAnalyticsQueryRequest->setEndDate(Carbon::parse($data['endDate'])->format('Y-m-d'));
            $searchAnalyticsQueryRequest->setDimensions(['query', 'page', 'country', 'device']);
            $searchAnalyticsQueryRequest->setRowLimit($data['limit'] ?? 50);
            $searchAnalyticsQueryRequest->setDataState('all'); // Include fresh data

            $response = $searchConsole->searchanalytics->query($project->url, $searchAnalyticsQueryRequest);

            $this->searchConsoleData = [
                'metadata' => [
                    'project_url' => $project->url,
                    'start_date' => $data['startDate'],
                    'end_date' => $data['endDate'],
                    'row_limit' => $data['limit'],
                    'total_rows' => count($response->getRows() ?? []),
                ],
                'aggregated_metrics' => [
                    'total_clicks' => 0,
                    'total_impressions' => 0,
                    'average_ctr' => 0,
                    'average_position' => 0,
                ],
                'rows' => [],
            ];

            if ($response->getRows()) {
                $totalClicks = 0;
                $totalImpressions = 0;
                $totalCtr = 0;
                $totalPosition = 0;
                $rowCount = 0;

                foreach ($response->getRows() as $row) {
                    $keys = $row->getKeys();
                    $rowData = [
                        'query' => $keys[0] ?? 'N/A',
                        'page' => $keys[1] ?? 'N/A',
                        'country' => $keys[2] ?? 'N/A',
                        'device' => $keys[3] ?? 'N/A',
                        'clicks' => $row->getClicks() ?? 0,
                        'impressions' => $row->getImpressions() ?? 0,
                        'ctr' => round(($row->getCtr() ?? 0) * 100, 2) . '%',
                        'position' => round($row->getPosition() ?? 0, 1),
                    ];

                    $this->searchConsoleData['rows'][] = $rowData;

                    $totalClicks += $row->getClicks() ?? 0;
                    $totalImpressions += $row->getImpressions() ?? 0;
                    $totalCtr += $row->getCtr() ?? 0;
                    $totalPosition += $row->getPosition() ?? 0;
                    $rowCount++;
                }

                $this->searchConsoleData['aggregated_metrics'] = [
                    'total_clicks' => $totalClicks,
                    'total_impressions' => $totalImpressions,
                    'average_ctr' => $rowCount > 0 ? round(($totalCtr / $rowCount) * 100, 2) . '%' : '0%',
                    'average_position' => $rowCount > 0 ? round($totalPosition / $rowCount, 1) : 0,
                ];
            }

            $this->selectedService = 'search_console';

            Notification::make()
                ->title('Search Console data fetched successfully')
                ->success()
                ->send();
        } catch (Exception $exception) {
            $this->errorMessage = 'Search Console Error: ' . $exception->getMessage();
            Log::error('Search Console Debug Error: ' . $exception->getMessage());

            Notification::make()
                ->title('Failed to fetch Search Console data')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function fetchAnalyticsData(array $data): void
    {
        try {
            $this->errorMessage = null;
            $project = Filament::getTenant();

            if (! $project instanceof Project) {
                throw new Exception('No project selected');
            }

            $manager = ApiServiceManager::forProject($project);
            /** @var GoogleAnalytics4Service $analytics */
            $analytics = $manager->getGoogleAnalytics4();

            if (! $analytics->isConfigured()) {
                throw new Exception('Google Analytics 4 not configured for this project');
            }

            // Use the comprehensive data fetching method
            $startDate = Carbon::parse($data['startDate']);
            $endDate = Carbon::parse($data['endDate']);

            $this->analyticsData = $analytics->getAllGA4Data($startDate, $endDate);
            $this->selectedService = 'analytics';

            Notification::make()
                ->title('Google Analytics 4 Data Successfully Fetched')
                ->body(sprintf('Fetched comprehensive GA4 data for date range %s to %s.',
                    $data['startDate'],
                    $data['endDate']
                ))
                ->success()
                ->send();
        } catch (ApiException $exception) {
            $this->errorMessage = 'Analytics API Error: ' . $exception->getMessage();
            Log::error('Analytics Debug API Error: ' . $exception->getMessage());

            Notification::make()
                ->title('Failed to fetch Analytics data')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        } catch (Exception $exception) {
            $this->errorMessage = 'Analytics Error: ' . $exception->getMessage();
            Log::error('Analytics Debug Error: ' . $exception->getMessage());

            Notification::make()
                ->title('Failed to fetch Analytics data')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function fetchGoogleAdsData(array $data): void
    {
        try {
            $this->errorMessage = null;
            $project = Filament::getTenant();

            if (! $project instanceof Project) {
                throw new Exception('No project selected');
            }

            // Initialize Google Ads service
            $googleAdsService = new GoogleAdsApiService($project);

            // Test connection and check credentials
            $connectionStatus = $googleAdsService->testConnection();

            // Additional credential check
            $credentialsExist = $this->checkGoogleAdsCredentials($project);

            $debugData = [
                'metadata' => [
                    'project' => $project->name,
                    'service' => 'google_ads',
                    'start_date' => $data['startDate'] ?? null,
                    'end_date' => $data['endDate'] ?? null,
                    'fetched_at' => now()->toIso8601String(),
                    'connection_status' => $connectionStatus,
                    'credentials_exist' => $credentialsExist,
                    'actual_status' => $connectionStatus && $credentialsExist,
                    'keyword_source' => ($data['use_project_keywords'] ?? true) ? 'project_keywords' : 'custom_keywords',
                    'keyword_limit' => (int) ($data['limit'] ?? 10),
                ],
            ];

            if (!$connectionStatus || !$credentialsExist) {
                $debugData['error'] = 'Google Ads API not configured or connection failed';
                $debugData['configuration_status'] = [
                    'has_credentials' => false,
                    'required_fields' => [
                        'client_id',
                        'client_secret',
                        'refresh_token',
                        'developer_token',
                        'customer_id'
                    ],
                    'note' => 'Using mock data for demonstration purposes',
                ];

                // Get keywords from form data
                $testKeywords = $this->getKeywordsFromData($data, $project);

                $mockKeywordData = [];
                $mockHistoricalData = [];

                foreach ($testKeywords as $keyword) {
                    $mockKeywordData[$keyword] = [
                        'keyword' => $keyword,
                        'search_volume' => rand(1000, 50000),
                        'competition' => round(rand(10, 90) / 100, 2),
                        'low_bid' => round(rand(50, 300) / 100, 2),
                        'high_bid' => round(rand(300, 1000) / 100, 2),
                        'difficulty' => rand(20, 85),
                    ];

                    $monthlyVolumes = [];
                    for ($i = 11; $i >= 0; $i--) {
                        $date = now()->subMonths($i);
                        $monthlyVolumes[] = [
                            'year' => $date->year,
                            'month' => $date->month,
                            'monthly_searches' => rand(800, 60000),
                        ];
                    }

                    $mockHistoricalData[$keyword] = [
                        'keyword' => $keyword,
                        'avg_monthly_searches' => rand(1000, 50000),
                        'competition' => round(rand(10, 90) / 100, 2),
                        'competition_index' => rand(20, 80),
                        'low_top_of_page_bid_micros' => rand(500000, 3000000),
                        'high_top_of_page_bid_micros' => rand(3000000, 10000000),
                        'low_top_of_page_bid' => round(rand(50, 300) / 100, 2),
                        'high_top_of_page_bid' => round(rand(300, 1000) / 100, 2),
                        'monthly_search_volumes' => $monthlyVolumes,
                    ];
                }

                $debugData['mock_data'] = true;
                $debugData['keyword_data'] = $mockKeywordData;
                $debugData['historical_metrics'] = $mockHistoricalData;
                $debugData['bulk_results'] = $mockKeywordData;

                // Add statistics
                $debugData['statistics'] = [
                    'keywords_tested' => count($testKeywords),
                    'successful_fetches' => count($mockKeywordData),
                    'historical_fetches' => count($mockHistoricalData),
                    'bulk_fetches' => count($mockKeywordData),
                ];

                // Add available geo targets
                $debugData['available_geo_targets'] = [
                    'HU' => 'Hungary',
                    'US' => 'United States',
                    'UK' => 'United Kingdom',
                    'DE' => 'Germany',
                    'FR' => 'France',
                ];
            } else {
                // Get keywords from form data
                $testKeywords = $this->getKeywordsFromData($data, $project);

                $keywordData = [];
                $historicalData = [];
                $bulkData = [];

                foreach ($testKeywords as $keyword) {
                    // Get regular keyword data
                    $kwData = $googleAdsService->getKeywordData($keyword, 'HU');
                    if ($kwData) {
                        $keywordData[$keyword] = $kwData;
                    }

                    // Get historical metrics
                    $histData = $googleAdsService->getHistoricalMetrics($keyword, 'HU');
                    if ($histData) {
                        $historicalData[$keyword] = $histData;
                    }
                }

                // Also test bulk operation with a few keywords
                $bulkKeywords = collect($testKeywords);
                $bulkResults = $googleAdsService->bulkGetKeywordData($bulkKeywords, 'HU');

                $debugData['keyword_data'] = $keywordData;
                $debugData['historical_metrics'] = $historicalData;
                $debugData['bulk_results'] = $bulkResults;

                // Add statistics
                $debugData['statistics'] = [
                    'keywords_tested' => count($testKeywords),
                    'successful_fetches' => count($keywordData),
                    'historical_fetches' => count($historicalData),
                    'bulk_fetches' => count($bulkResults),
                ];

                // Add available geo targets
                $debugData['available_geo_targets'] = [
                    'HU' => 'Hungary',
                    'US' => 'United States',
                    'UK' => 'United Kingdom',
                    'DE' => 'Germany',
                    'FR' => 'France',
                ];
            }

            $this->googleAdsData = $debugData;
            $this->selectedService = 'google_ads';

            $actuallyWorking = $connectionStatus && $credentialsExist;

            Notification::make()
                ->title($actuallyWorking ? 'Google Ads data fetched successfully' : 'Google Ads not properly configured')
                ->body($actuallyWorking
                    ? 'All API calls completed successfully'
                    : (!$credentialsExist
                        ? 'Missing Google Ads API credentials'
                        : 'Connection test failed'))
                ->when($actuallyWorking, fn($notification) => $notification->success())
                ->when(!$actuallyWorking, fn($notification) => $notification->warning())
                ->send();
        } catch (Exception $exception) {
            $this->errorMessage = 'Google Ads Error: ' . $exception->getMessage();
            Log::error('Google Ads Debug Error: ' . $exception->getMessage());

            Notification::make()
                ->title('Failed to fetch Google Ads data')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Get keywords array from form data, either from project keywords or custom input
     */
    private function getKeywordsFromData(array $data, Project $project): array
    {
        $limit = (int) ($data['limit'] ?? 10);

        if (($data['use_project_keywords'] ?? true) === true) {
            // Use project keywords
            if (!empty($data['keywords'])) {
                // User selected specific keywords
                $keywords = array_slice($data['keywords'], 0, $limit);
            } else {
                // Use random keywords from project
                $keywords = $project->keywords()
                    ->inRandomOrder()
                    ->limit($limit)
                    ->pluck('keyword')
                    ->toArray();
            }

            // Fallback to default if no project keywords
            if (empty($keywords)) {
                return ['seo', 'marketing', 'google ads'];
            }

            return $keywords;
        } else {
            // Use custom keywords from text input
            if (!empty($data['custom_keywords'])) {
                $keywords = array_map('trim', explode(',', $data['custom_keywords']));
                return array_slice(array_filter($keywords), 0, $limit);
            }

            // Fallback to default
            return ['seo', 'marketing', 'google ads'];
        }
    }

    /**
     * Check if Google Ads credentials exist and are complete
     */
    private function checkGoogleAdsCredentials(Project $project): bool
    {
        $credential = ApiCredential::where('project_id', $project->id)
            ->where('service', 'google_ads')
            ->first();

        if (!$credential) {
            return false;
        }

        $requiredFields = [
            'client_id',
            'client_secret',
            'refresh_token',
            'developer_token',
            'customer_id'
        ];

        foreach ($requiredFields as $field) {
            if (empty($credential->$field)) {
                return false;
            }
        }

        return true;
    }
}
