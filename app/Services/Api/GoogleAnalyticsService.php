<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\ApiCredential;
use Carbon\Carbon;
use Exception;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Analytics\Data\V1beta\RunReportResponse;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GoogleAnalyticsService extends BaseApiService
{
    protected string $serviceName = 'google_analytics_4';

    private ?BetaAnalyticsDataClient $betaAnalyticsDataClient = null;

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        // GA4 uses its own client, not HTTP requests
    }

    public function testConnection(): bool
    {
        try {
            $client = $this->getClient();
            $propertyId = $this->getCredential('property_id');

            if (! $client instanceof BetaAnalyticsDataClient || ! $propertyId) {
                return false;
            }

            // Test with a simple query
            $request = (new RunReportRequest())
                ->setProperty('properties/' . $propertyId)
                ->setDateRanges([
                    new DateRange([
                        'start_date' => '7daysAgo',
                        'end_date' => 'today',
                    ]),
                ])
                ->setMetrics([new Metric(['name' => 'sessions'])])
                ->setLimit(1);

            $client->runReport($request);

            return true;
        } catch (Exception $exception) {
            Log::error('GA4 test connection failed', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function getClient(): ?BetaAnalyticsDataClient
    {
        if ($this->betaAnalyticsDataClient instanceof BetaAnalyticsDataClient) {
            return $this->betaAnalyticsDataClient;
        }

        try {
            // First, try to get credentials from the stored service account file
            $credentials = null;

            if ($this->credentials instanceof ApiCredential && $this->credentials->service_account_file) {
                // Use the stored service account file
                $credentials = $this->credentials->service_account_json;
            }

            // Fallback to credentials array
            if (! $credentials) {
                $credentials = $this->getCredential('service_account_json');
            }

            if (! $credentials) {
                throw new Exception('Service account JSON credentials not found');
            }

            // If credentials is a string, decode it
            if (is_string($credentials)) {
                $credentials = json_decode($credentials, true);
            }

            $serviceAccountCredentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/analytics.readonly',
                $credentials,
            );

            $this->betaAnalyticsDataClient = new BetaAnalyticsDataClient([
                'credentials' => $serviceAccountCredentials,
            ]);

            return $this->betaAnalyticsDataClient;
        } catch (Exception $exception) {
            Log::error('GA4 client error', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function getOrganicTrafficData(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate ??= now()->subDays(30);
        $endDate ??= now()->subDays(1);

        try {
            $client = $this->getClient();
            $propertyId = $this->getCredential('property_id');

            if (! $client instanceof BetaAnalyticsDataClient || ! $propertyId) {
                return new Collection();
            }

            $request = (new RunReportRequest())
                ->setProperty('properties/' . $propertyId)
                ->setDateRanges([
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ])
                ->setDimensions([
                    new Dimension(['name' => 'date']),
                    new Dimension(['name' => 'sessionDefaultChannelGroup']),
                ])
                ->setMetrics([
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'bounceRate']),
                    new Metric(['name' => 'averageSessionDuration']),
                ])
                ->setDimensionFilter(
                    new FilterExpression([
                        'filter' => new Filter([
                            'field_name' => 'sessionDefaultChannelGroup',
                            'string_filter' => new StringFilter([
                                'match_type' => MatchType::EXACT,
                                'value' => 'Organic Search',
                            ]),
                        ]),
                    ]),
                );

            $response = $client->runReport($request);

            return $this->processGA4Data($response);
        } catch (Exception $exception) {
            Log::error('GA4 organic traffic data error', [
                'error' => $exception->getMessage(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]);

            return new Collection();
        }
    }

    public function getTopOrganicPages(?Carbon $startDate = null, ?Carbon $endDate = null, int $maxResults = 50): Collection
    {
        $startDate ??= now()->subDays(30);
        $endDate ??= now()->subDays(1);

        try {
            $client = $this->getClient();
            $propertyId = $this->getCredential('property_id');

            if (! $client instanceof BetaAnalyticsDataClient || ! $propertyId) {
                return new Collection();
            }

            $request = (new RunReportRequest())
                ->setProperty('properties/' . $propertyId)
                ->setDateRanges([
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ])
                ->setDimensions([
                    new Dimension(['name' => 'pagePath']),
                    new Dimension(['name' => 'pageTitle']),
                ])
                ->setMetrics([
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'bounceRate']),
                ])
                ->setDimensionFilter(
                    new FilterExpression([
                        'filter' => new Filter([
                            'field_name' => 'sessionDefaultChannelGroup',
                            'string_filter' => new StringFilter([
                                'match_type' => MatchType::EXACT,
                                'value' => 'Organic Search',
                            ]),
                        ]),
                    ]),
                )
                ->setOrderBys([
                    new OrderBy([
                        'metric' => new MetricOrderBy(['metric_name' => 'sessions']),
                        'desc' => true,
                    ]),
                ])
                ->setLimit($maxResults);

            $response = $client->runReport($request);

            return $this->processGA4Data($response);
        } catch (Exception $exception) {
            Log::error('GA4 top organic pages error', [
                'error' => $exception->getMessage(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]);

            return new Collection();
        }
    }

    public function getAudienceOverview(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate ??= now()->subDays(30);
        $endDate ??= now()->subDays(1);

        try {
            $client = $this->getClient();
            $propertyId = $this->getCredential('property_id');

            if (! $client instanceof BetaAnalyticsDataClient || ! $propertyId) {
                return new Collection();
            }

            $request = (new RunReportRequest())
                ->setProperty('properties/' . $propertyId)
                ->setDateRanges([
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ])
                ->setDimensions([
                    new Dimension(['name' => 'date']),
                ])
                ->setMetrics([
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'newUsers']),
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'bounceRate']),
                    new Metric(['name' => 'averageSessionDuration']),
                ]);

            $response = $client->runReport($request);

            return $this->processGA4Data($response);
        } catch (Exception $exception) {
            Log::error('GA4 audience overview error', [
                'error' => $exception->getMessage(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]);

            return new Collection();
        }
    }

    public function getTopTrafficChannels(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate ??= now()->subDays(30);
        $endDate ??= now()->subDays(1);

        try {
            $client = $this->getClient();
            $propertyId = $this->getCredential('property_id');

            if (! $client instanceof BetaAnalyticsDataClient || ! $propertyId) {
                return new Collection();
            }

            $request = (new RunReportRequest())
                ->setProperty('properties/' . $propertyId)
                ->setDateRanges([
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ])
                ->setDimensions([
                    new Dimension(['name' => 'sessionDefaultChannelGroup']),
                ])
                ->setMetrics([
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'bounceRate']),
                    new Metric(['name' => 'averageSessionDuration']),
                ])
                ->setOrderBys([
                    new OrderBy([
                        'metric' => new MetricOrderBy(['metric_name' => 'sessions']),
                        'desc' => true,
                    ]),
                ]);

            $response = $client->runReport($request);

            return $this->processGA4Data($response);
        } catch (Exception $exception) {
            Log::error('GA4 traffic channels error', [
                'error' => $exception->getMessage(),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]);

            return new Collection();
        }
    }

    private function processGA4Data(RunReportResponse $runReportResponse): Collection
    {
        $processedData = [];

        foreach ($runReportResponse->getRows() as $row) {
            $rowData = [];

            // Add dimensions
            foreach ($row->getDimensionValues() as $index => $dimensionValue) {
                $dimensionName = $runReportResponse->getDimensionHeaders()[$index]->getName();
                $rowData[$dimensionName] = $dimensionValue->getValue();
            }

            // Add metrics
            foreach ($row->getMetricValues() as $index => $metricValue) {
                $metricName = $runReportResponse->getMetricHeaders()[$index]->getName();
                $value = $metricValue->getValue();

                // Convert specific metrics
                if ($metricName == 'bounceRate') {
                    $rowData[$metricName] = round(floatval($value) * 100, 2); // Convert to percentage
                } elseif ($metricName == 'averageSessionDuration') {
                    $rowData[$metricName] = round(floatval($value), 2);
                } else {
                    $rowData[$metricName] = intval($value);
                }
            }

            $processedData[] = $rowData;
        }

        return new Collection($processedData);
    }

    /**
     * Get comprehensive GA4 data for debugging and analysis
     */
    public function getAllGA4Data(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        try {
            $client = $this->getClient();
            $propertyId = $this->getCredential('property_id');

            if (! $client instanceof BetaAnalyticsDataClient || ! $propertyId) {
                throw new Exception('GA4 client or property ID not configured');
            }

            $startDate ??= Carbon::now()->subDays(30);
            $endDate ??= Carbon::now();

            return [
                'overview' => $this->getOverviewData($client, $propertyId, $startDate, $endDate),
                'traffic_sources' => $this->getTrafficSourcesData($client, $propertyId, $startDate, $endDate),
                'top_pages' => $this->getTopPagesData($client, $propertyId, $startDate, $endDate),
                'user_demographics' => $this->getUserDemographicsData($client, $propertyId, $startDate, $endDate),
                'device_data' => $this->getDeviceData($client, $propertyId, $startDate, $endDate),
                'conversion_data' => $this->getConversionData($client, $propertyId, $startDate, $endDate),
                'real_time' => $this->getRealTimeData($client, $propertyId),
            ];
        } catch (Exception $exception) {
            Log::error('GA4 getAllData error', [
                'error' => $exception->getMessage(),
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate?->format('Y-m-d'),
            ]);

            throw $exception;
        }
    }

    private function getOverviewData(BetaAnalyticsDataClient $betaAnalyticsDataClient, string $propertyId, Carbon $startDate, Carbon $endDate): array
    {
        $runReportRequest = (new RunReportRequest())
            ->setProperty('properties/' . $propertyId)
            ->setDateRanges([
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ])
            ->setMetrics([
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'newUsers']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'conversions']),
            ]);

        $runReportResponse = $betaAnalyticsDataClient->runReport($runReportRequest);

        return $this->processGA4Data($runReportResponse)->first() ?? [];
    }

    private function getTrafficSourcesData(BetaAnalyticsDataClient $betaAnalyticsDataClient, string $propertyId, Carbon $startDate, Carbon $endDate): array
    {
        $runReportRequest = (new RunReportRequest())
            ->setProperty('properties/' . $propertyId)
            ->setDateRanges([
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ])
            ->setDimensions([
                new Dimension(['name' => 'sessionDefaultChannelGroup']),
                new Dimension(['name' => 'sessionSourceMedium']),
            ])
            ->setMetrics([
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'conversions']),
            ])
            ->setOrderBys([
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'sessions']),
                    'desc' => true,
                ]),
            ])
            ->setLimit(20);

        $runReportResponse = $betaAnalyticsDataClient->runReport($runReportRequest);

        return $this->processGA4Data($runReportResponse)->toArray();
    }

    private function getTopPagesData(BetaAnalyticsDataClient $betaAnalyticsDataClient, string $propertyId, Carbon $startDate, Carbon $endDate): array
    {
        $runReportRequest = (new RunReportRequest())
            ->setProperty('properties/' . $propertyId)
            ->setDateRanges([
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ])
            ->setDimensions([
                new Dimension(['name' => 'pagePath']),
                new Dimension(['name' => 'pageTitle']),
            ])
            ->setMetrics([
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'bounceRate']),
            ])
            ->setOrderBys([
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                    'desc' => true,
                ]),
            ])
            ->setLimit(20);

        $runReportResponse = $betaAnalyticsDataClient->runReport($runReportRequest);

        return $this->processGA4Data($runReportResponse)->toArray();
    }

    private function getUserDemographicsData(BetaAnalyticsDataClient $betaAnalyticsDataClient, string $propertyId, Carbon $startDate, Carbon $endDate): array
    {
        $runReportRequest = (new RunReportRequest())
            ->setProperty('properties/' . $propertyId)
            ->setDateRanges([
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ])
            ->setDimensions([
                new Dimension(['name' => 'country']),
                new Dimension(['name' => 'city']),
                new Dimension(['name' => 'language']),
            ])
            ->setMetrics([
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'screenPageViews']),
            ])
            ->setOrderBys([
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'activeUsers']),
                    'desc' => true,
                ]),
            ])
            ->setLimit(15);

        $runReportResponse = $betaAnalyticsDataClient->runReport($runReportRequest);

        return $this->processGA4Data($runReportResponse)->toArray();
    }

    private function getDeviceData(BetaAnalyticsDataClient $betaAnalyticsDataClient, string $propertyId, Carbon $startDate, Carbon $endDate): array
    {
        $runReportRequest = (new RunReportRequest())
            ->setProperty('properties/' . $propertyId)
            ->setDateRanges([
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ])
            ->setDimensions([
                new Dimension(['name' => 'deviceCategory']),
                new Dimension(['name' => 'operatingSystem']),
                new Dimension(['name' => 'browser']),
            ])
            ->setMetrics([
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
            ])
            ->setOrderBys([
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'sessions']),
                    'desc' => true,
                ]),
            ])
            ->setLimit(15);

        $runReportResponse = $betaAnalyticsDataClient->runReport($runReportRequest);

        return $this->processGA4Data($runReportResponse)->toArray();
    }

    private function getConversionData(BetaAnalyticsDataClient $betaAnalyticsDataClient, string $propertyId, Carbon $startDate, Carbon $endDate): array
    {
        $runReportRequest = (new RunReportRequest())
            ->setProperty('properties/' . $propertyId)
            ->setDateRanges([
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ])
            ->setDimensions([
                new Dimension(['name' => 'eventName']),
            ])
            ->setMetrics([
                new Metric(['name' => 'eventCount']),
                new Metric(['name' => 'conversions']),
                new Metric(['name' => 'totalRevenue']),
            ])
            ->setOrderBys([
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'eventCount']),
                    'desc' => true,
                ]),
            ])
            ->setLimit(10);

        $runReportResponse = $betaAnalyticsDataClient->runReport($runReportRequest);

        return $this->processGA4Data($runReportResponse)->toArray();
    }

    private function getRealTimeData(BetaAnalyticsDataClient $betaAnalyticsDataClient, string $propertyId): array
    {
        try {
            // Real-time data requires different approach
            $request = (new RunReportRequest())
                ->setProperty('properties/' . $propertyId)
                ->setDateRanges([
                    new DateRange([
                        'start_date' => 'today',
                        'end_date' => 'today',
                    ]),
                ])
                ->setMetrics([
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'screenPageViews']),
                ])
                ->setDimensions([
                    new Dimension(['name' => 'country']),
                ])
                ->setLimit(5);

            $response = $betaAnalyticsDataClient->runReport($request);

            return $this->processGA4Data($response)->toArray();
        } catch (Exception $exception) {
            Log::warning('GA4 real-time data not available', ['error' => $exception->getMessage()]);

            return [];
        }
    }
}
