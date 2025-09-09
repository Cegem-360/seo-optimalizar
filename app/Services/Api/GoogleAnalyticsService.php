<?php

namespace App\Services\Api;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;

class GoogleAnalyticsService extends BaseApiService
{
    protected string $serviceName = 'google_analytics';

    private string $baseUrl = 'https://analyticsreporting.googleapis.com/v4';

    private ?string $accessToken = null;

    protected function configureRequest(PendingRequest $pendingRequest): void
    {
        if ($this->accessToken === null || $this->accessToken === '' || $this->accessToken === '0') {
            $this->refreshAccessToken();
        }

        $pendingRequest->withToken($this->accessToken)
            ->accept('application/json');
    }

    private function refreshAccessToken(): void
    {
        $refreshToken = $this->getCredential('refresh_token');
        $clientId = $this->getCredential('client_id');
        $clientSecret = $this->getCredential('client_secret');

        if (! $refreshToken || ! $clientId || ! $clientSecret) {
            throw new \Exception('Missing Google Analytics credentials');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to refresh Google Analytics access token');
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];
    }

    public function testConnection(): bool
    {
        try {
            // Test with a simple query
            $propertyId = $this->getCredential('property_id');
            if (! $propertyId) {
                return false;
            }

            $response = $this->makeRequest()->post($this->baseUrl . '/reports:batchGet', [
                'reportRequests' => [
                    [
                        'viewId' => $propertyId,
                        'dateRanges' => [
                            [
                                'startDate' => '7daysAgo',
                                'endDate' => 'today',
                            ],
                        ],
                        'metrics' => [
                            ['expression' => 'ga:sessions'],
                        ],
                        'dimensions' => [
                            ['name' => 'ga:date'],
                        ],
                    ],
                ],
            ]);

            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    public function getOrganicTrafficData(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate ??= now()->subDays(30);
        $endDate ??= now()->subDays(1);
        $propertyId = $this->getCredential('property_id');

        $payload = [
            'reportRequests' => [
                [
                    'viewId' => $propertyId,
                    'dateRanges' => [
                        [
                            'startDate' => $startDate->format('Y-m-d'),
                            'endDate' => $endDate->format('Y-m-d'),
                        ],
                    ],
                    'metrics' => [
                        ['expression' => 'ga:sessions'],
                        ['expression' => 'ga:users'],
                        ['expression' => 'ga:pageviews'],
                        ['expression' => 'ga:bounceRate'],
                        ['expression' => 'ga:avgSessionDuration'],
                    ],
                    'dimensions' => [
                        ['name' => 'ga:date'],
                        ['name' => 'ga:medium'],
                    ],
                    'dimensionFilterClauses' => [
                        [
                            'filters' => [
                                [
                                    'dimensionName' => 'ga:medium',
                                    'operator' => 'EXACT',
                                    'expressions' => ['organic'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->makeRequest()->post($this->baseUrl . '/reports:batchGet', $payload);
        $data = $this->handleResponse($response);

        return $this->processAnalyticsData($data);
    }

    public function getTopOrganicPages(?Carbon $startDate = null, ?Carbon $endDate = null, int $maxResults = 50): Collection
    {
        $startDate ??= now()->subDays(30);
        $endDate ??= now()->subDays(1);
        $propertyId = $this->getCredential('property_id');

        $payload = [
            'reportRequests' => [
                [
                    'viewId' => $propertyId,
                    'dateRanges' => [
                        [
                            'startDate' => $startDate->format('Y-m-d'),
                            'endDate' => $endDate->format('Y-m-d'),
                        ],
                    ],
                    'metrics' => [
                        ['expression' => 'ga:sessions'],
                        ['expression' => 'ga:users'],
                        ['expression' => 'ga:pageviews'],
                        ['expression' => 'ga:bounceRate'],
                    ],
                    'dimensions' => [
                        ['name' => 'ga:pagePath'],
                        ['name' => 'ga:pageTitle'],
                    ],
                    'dimensionFilterClauses' => [
                        [
                            'filters' => [
                                [
                                    'dimensionName' => 'ga:medium',
                                    'operator' => 'EXACT',
                                    'expressions' => ['organic'],
                                ],
                            ],
                        ],
                    ],
                    'orderBys' => [
                        [
                            'fieldName' => 'ga:sessions',
                            'sortOrder' => 'DESCENDING',
                        ],
                    ],
                    'pageSize' => $maxResults,
                ],
            ],
        ];

        $response = $this->makeRequest()->post($this->baseUrl . '/reports:batchGet', $payload);
        $data = $this->handleResponse($response);

        return $this->processAnalyticsData($data);
    }

    public function getOrganicKeywordData(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate ??= now()->subDays(30);
        $endDate ??= now()->subDays(1);
        $propertyId = $this->getCredential('property_id');

        // Note: Google Analytics 4 no longer provides keyword data due to "not provided"
        // This method would need to be adapted for GA4's different data model
        $payload = [
            'reportRequests' => [
                [
                    'viewId' => $propertyId,
                    'dateRanges' => [
                        [
                            'startDate' => $startDate->format('Y-m-d'),
                            'endDate' => $endDate->format('Y-m-d'),
                        ],
                    ],
                    'metrics' => [
                        ['expression' => 'ga:sessions'],
                        ['expression' => 'ga:users'],
                    ],
                    'dimensions' => [
                        ['name' => 'ga:keyword'],
                    ],
                    'dimensionFilterClauses' => [
                        [
                            'filters' => [
                                [
                                    'dimensionName' => 'ga:medium',
                                    'operator' => 'EXACT',
                                    'expressions' => ['organic'],
                                ],
                                [
                                    'dimensionName' => 'ga:keyword',
                                    'operator' => 'NOT_EXACT',
                                    'expressions' => ['(not provided)', '(not set)'],
                                ],
                            ],
                        ],
                    ],
                    'orderBys' => [
                        [
                            'fieldName' => 'ga:sessions',
                            'sortOrder' => 'DESCENDING',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->makeRequest()->post($this->baseUrl . '/reports:batchGet', $payload);
        $data = $this->handleResponse($response);

        return $this->processAnalyticsData($data);
    }

    private function processAnalyticsData(array $data): Collection
    {
        $reports = $data['reports'] ?? [];
        $processedData = [];

        foreach ($reports as $report) {
            $columnHeaders = $report['columnHeader'] ?? [];
            $dimensionHeaders = $columnHeaders['dimensions'] ?? [];
            $metricHeaders = $columnHeaders['metricHeader']['metricHeaderEntries'] ?? [];

            $rows = $report['data']['rows'] ?? [];

            foreach ($rows as $row) {
                $dimensions = $row['dimensions'] ?? [];
                $metrics = $row['metrics'][0]['values'] ?? [];

                $rowData = [];

                // Add dimensions
                foreach ($dimensionHeaders as $index => $header) {
                    $rowData[str_replace('ga:', '', $header)] = $dimensions[$index] ?? null;
                }

                // Add metrics
                foreach ($metricHeaders as $index => $header) {
                    $metricName = str_replace('ga:', '', $header['name']);
                    $value = $metrics[$index] ?? 0;

                    // Convert percentage metrics
                    $value = $metricName == 'bounceRate' ? round(floatval($value), 2) : intval($value);

                    $rowData[$metricName] = $value;
                }

                $processedData[] = $rowData;
            }
        }

        return new \Illuminate\Support\Collection($processedData);
    }
}
