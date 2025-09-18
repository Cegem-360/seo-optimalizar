<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use App\Services\GoogleSearchConsoleService;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\ApiCore\ApiException;
use Google\Client as GoogleClient;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ApiDataDebugger extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static string $view = 'filament.pages.api-data-debugger';

    protected static ?string $navigationLabel = 'API Data Debugger';

    protected static ?string $title = 'API Raw Data Viewer';

    protected static ?string $navigationGroup = 'System Tools';

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
                ->form([
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->default(Carbon::now()->subDays(7))
                        ->required(),
                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->default(Carbon::now())
                        ->required(),
                    TextInput::make('limit')
                        ->label('Row Limit')
                        ->numeric()
                        ->default(50)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->fetchSearchConsoleData($data);
                }),

            Action::make('fetchAnalytics')
                ->label('Fetch Analytics Data')
                ->icon('heroicon-o-chart-bar')
                ->color('success')
                ->form([
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->default(Carbon::now()->subDays(7))
                        ->required(),
                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->default(Carbon::now())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->fetchAnalyticsData($data);
                }),

            Action::make('fetchGoogleAds')
                ->label('Fetch Google Ads Data')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->form([
                    DatePicker::make('startDate')
                        ->label('Start Date')
                        ->default(Carbon::now()->subDays(7))
                        ->required(),
                    DatePicker::make('endDate')
                        ->label('End Date')
                        ->default(Carbon::now())
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->fetchGoogleAdsData($data);
                }),
        ];
    }

    private function fetchSearchConsoleData(array $data): void
    {
        try {
            $this->errorMessage = null;
            $project = Filament::getTenant();

            if (!$project instanceof Project) {
                throw new Exception('No project selected');
            }

            $searchConsoleService = app(GoogleSearchConsoleService::class);

            if (!$searchConsoleService->hasCredentials()) {
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

            $request = new SearchAnalyticsQueryRequest();
            $request->setStartDate(Carbon::parse($data['startDate'])->format('Y-m-d'));
            $request->setEndDate(Carbon::parse($data['endDate'])->format('Y-m-d'));
            $request->setDimensions(['query', 'page', 'country', 'device']);
            $request->setRowLimit($data['limit'] ?? 50);
            $request->setDataState('all'); // Include fresh data

            $response = $searchConsole->searchanalytics->query($project->url, $request);

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
                'rows' => []
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

        } catch (Exception $e) {
            $this->errorMessage = 'Search Console Error: ' . $e->getMessage();
            Log::error('Search Console Debug Error: ' . $e->getMessage());

            Notification::make()
                ->title('Failed to fetch Search Console data')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function fetchAnalyticsData(array $data): void
    {
        try {
            $this->errorMessage = null;
            $project = Filament::getTenant();

            if (!$project instanceof Project) {
                throw new Exception('No project selected');
            }

            // For now, show placeholder
            $this->analyticsData = [
                'metadata' => [
                    'status' => 'Google Analytics integration pending configuration',
                    'project' => $project->name,
                    'start_date' => $data['startDate'],
                    'end_date' => $data['endDate'],
                ],
                'note' => 'Analytics API requires property ID configuration in project settings.',
            ];

            $this->selectedService = 'analytics';

            Notification::make()
                ->title('Analytics data placeholder loaded')
                ->success()
                ->send();

        } catch (Exception $e) {
            $this->errorMessage = 'Analytics Error: ' . $e->getMessage();
            Log::error('Analytics Debug Error: ' . $e->getMessage());

            Notification::make()
                ->title('Failed to fetch Analytics data')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function fetchGoogleAdsData(array $data): void
    {
        try {
            $this->errorMessage = null;
            $project = Filament::getTenant();

            if (!$project instanceof Project) {
                throw new Exception('No project selected');
            }

            // For now, show placeholder
            $this->googleAdsData = [
                'metadata' => [
                    'status' => 'Google Ads API integration pending',
                    'project' => $project->name,
                    'start_date' => $data['startDate'],
                    'end_date' => $data['endDate'],
                ],
                'note' => 'Google Ads API requires additional OAuth setup and customer ID configuration.',
            ];

            $this->selectedService = 'google_ads';

            Notification::make()
                ->title('Google Ads data status checked')
                ->success()
                ->send();

        } catch (Exception $e) {
            $this->errorMessage = 'Google Ads Error: ' . $e->getMessage();
            Log::error('Google Ads Debug Error: ' . $e->getMessage());

            Notification::make()
                ->title('Failed to fetch Google Ads data')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
