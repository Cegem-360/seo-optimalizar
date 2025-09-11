<?php

namespace App\Services\Api;

use Carbon\Carbon;
use Exception;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GoogleAnalytics4Service extends BaseApiService
{
    protected string $serviceName = 'google_analytics_4';

    private ?BetaAnalyticsDataClient $client = null;

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        // GA4 uses its own client, not HTTP requests
    }

    public function testConnection(): bool
    {
        try {
            $client = $this->getClient();
            $propertyId = $this->getCredential('property_id');

            if (! $client || ! $propertyId) {
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

            $response = $client->runReport($request);

            return $response !== null;
        } catch (Exception $e) {
            Log::error('GA4 test connection failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function getClient(): ?BetaAnalyticsDataClient
    {
        if ($this->client instanceof BetaAnalyticsDataClient) {
            return $this->client;
        }

        try {
            // First, try to get credentials from the stored service account file
            $credentials = null;

            if ($this->credentials && $this->credentials->service_account_file) {
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
                $credentials
            );

            $this->client = new BetaAnalyticsDataClient([
                'credentials' => $serviceAccountCredentials,
            ]);

            return $this->client;
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

            if (! $client || ! $propertyId) {
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
                            'string_filter' => new Filter\StringFilter([
                                'match_type' => Filter\StringFilter\MatchType::EXACT,
                                'value' => 'Organic Search',
                            ]),
                        ]),
                    ])
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

            if (! $client || ! $propertyId) {
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
                            'string_filter' => new Filter\StringFilter([
                                'match_type' => Filter\StringFilter\MatchType::EXACT,
                                'value' => 'Organic Search',
                            ]),
                        ]),
                    ])
                )
                ->setOrderBys([
                    new OrderBy([
                        'metric' => new OrderBy\MetricOrderBy(['metric_name' => 'sessions']),
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

            if (! $client || ! $propertyId) {
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

            if (! $client || ! $propertyId) {
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
                        'metric' => new OrderBy\MetricOrderBy(['metric_name' => 'sessions']),
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

    private function processGA4Data($response): Collection
    {
        $processedData = [];

        foreach ($response->getRows() as $row) {
            $rowData = [];

            // Add dimensions
            foreach ($row->getDimensionValues() as $index => $dimensionValue) {
                $dimensionName = $response->getDimensionHeaders()[$index]->getName();
                $rowData[$dimensionName] = $dimensionValue->getValue();
            }

            // Add metrics
            foreach ($row->getMetricValues() as $index => $metricValue) {
                $metricName = $response->getMetricHeaders()[$index]->getName();
                $value = $metricValue->getValue();

                // Convert specific metrics
                if (in_array($metricName, ['bounceRate'])) {
                    $rowData[$metricName] = round(floatval($value) * 100, 2); // Convert to percentage
                } elseif (in_array($metricName, ['averageSessionDuration'])) {
                    $rowData[$metricName] = round(floatval($value), 2);
                } else {
                    $rowData[$metricName] = intval($value);
                }
            }

            $processedData[] = $rowData;
        }

        return new Collection($processedData);
    }
}
