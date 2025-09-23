<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleAnalytics4Widget extends ChartWidget
{
    protected ?string $heading = 'Google Analytics 4 - Organic Traffic';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 3 months',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $tenant = Filament::getTenant();
        $days = (int) $this->filter;

        if (! $tenant instanceof Project) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        try {
            $apiManager = ApiServiceManager::forProject($tenant);

            if (! $apiManager->hasService('google_analytics_4')) {
                return $this->getEmptyData('Google Analytics 4 not configured');
            }

            $ga4Service = $apiManager->getGoogleAnalytics4();
            $endDate = Carbon::now()->subDays(1); // GA4 has 1-day delay
            $startDate = Carbon::now()->subDays($days + 1);

            $data = $ga4Service->getOrganicTrafficData($startDate, $endDate);

            if ($data->isEmpty()) {
                return $this->getEmptyData('No data available');
            }

            $labels = [];
            $sessions = [];
            $users = [];
            $pageViews = [];
            $bounceRates = [];

            foreach ($data as $row) {
                $labels[] = Carbon::parse($row['date'])->format('M d');
                $sessions[] = $row['sessions'] ?? 0;
                $users[] = $row['activeUsers'] ?? 0;
                $pageViews[] = $row['screenPageViews'] ?? 0;
                $bounceRates[] = $row['bounceRate'] ?? 0;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Sessions',
                        'data' => $sessions,
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'yAxisID' => 'y',
                        'tension' => 0.3,
                    ],
                    [
                        'label' => 'Active Users',
                        'data' => $users,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'yAxisID' => 'y',
                        'tension' => 0.3,
                    ],
                    [
                        'label' => 'Page Views',
                        'data' => $pageViews,
                        'borderColor' => '#f59e0b',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                        'yAxisID' => 'y',
                        'tension' => 0.3,
                        'hidden' => true, // Hidden by default
                    ],
                    [
                        'label' => 'Bounce Rate (%)',
                        'data' => $bounceRates,
                        'borderColor' => '#ef4444',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'yAxisID' => 'y1',
                        'tension' => 0.3,
                        'hidden' => true, // Hidden by default
                    ],
                ],
                'labels' => $labels,
            ];
        } catch (Exception $exception) {
            Log::error('GA4 Widget error', [
                'project_id' => $tenant->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->getEmptyData('Error loading GA4 data');
        }
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Sessions / Users / Page Views',
                    ],
                    'beginAtZero' => true,
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Bounce Rate (%)',
                    ],
                    'beginAtZero' => true,
                    'max' => 100,
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }

    private function getEmptyData(string $message): array
    {
        $this->heading = $this->heading . ' - ' . $message;

        return [
            'datasets' => [],
            'labels' => [],
        ];
    }
}
