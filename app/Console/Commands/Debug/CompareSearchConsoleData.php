<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Models\ApiCredential;
use App\Models\Project;
use App\Services\Api\GoogleSearchConsoleService;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CompareSearchConsoleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:search-console-compare
        {project : Project ID}
        {--start-date= : Start date (YYYY-MM-DD)}
        {--end-date= : End date (YYYY-MM-DD)}
        {--keyword= : Specific keyword to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare Search Console API data with web interface for debugging';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->argument('project');
        $project = Project::query()->find($projectId);

        if (! $project) {
            $this->error(sprintf('Project with ID %s not found.', $projectId));

            return 1;
        }

        $this->info('Testing Search Console data for project: ' . $project->name);
        $this->info('Project URL: ' . $project->url);

        // Check for API credentials
        /** @var ApiCredential|null $apiCredential */
        $apiCredential = $project->apiCredentials()
            ->where('service', 'google_search_console')
            ->where('is_active', true)
            ->first();

        if (! $apiCredential) {
            $this->error('No active Google Search Console API credentials found for this project.');

            return 1;
        }

        $propertyUrl = $apiCredential->getCredential('property_url');
        $this->info('Property URL in credentials: ' . $propertyUrl);

        // Date range
        $startDate = $this->option('start-date')
            ? Carbon::parse($this->option('start-date'))
            : Carbon::now()->subDays(7);

        $endDate = $this->option('end-date')
            ? Carbon::parse($this->option('end-date'))
            : Carbon::now()->subDays(1);

        $this->info(sprintf('Date range: %s to %s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d')));
        $this->newLine();

        // Initialize service
        $googleSearchConsoleService = new GoogleSearchConsoleService($project);

        try {
            // Test connection first
            $this->info('Testing connection...');
            if (! $googleSearchConsoleService->testConnection()) {
                $this->error('Connection test failed. Check the logs for details.');

                return 1;
            }

            $this->info('✅ Connection successful!');
            $this->newLine();

            // Get general search analytics
            $this->info('Fetching search analytics data...');
            $analytics = $googleSearchConsoleService->getSearchAnalytics(['query'], $startDate, $endDate, 25);

            if ($analytics->isEmpty()) {
                $this->warn('No data returned from API.');
            } else {
                $this->info(sprintf('Found %d results', $analytics->count()));
                $this->newLine();

                // Display table with results
                $this->info('Top 10 queries from API:');
                $this->table(
                    ['Query', 'Clicks', 'Impressions', 'CTR', 'Position'],
                    $analytics->take(10)->map(fn ($row): array => [
                        'query' => $row['keys'][0] ?? 'N/A',
                        'clicks' => $row['clicks'] ?? 0,
                        'impressions' => $row['impressions'] ?? 0,
                        'ctr' => round(($row['ctr'] ?? 0) * 100, 2) . '%',
                        'position' => round($row['position'] ?? 0, 1),
                    ])->toArray()
                );
            }

            // Check specific keyword if provided
            if ($keyword = $this->option('keyword')) {
                $this->newLine();
                $this->info(sprintf("Checking specific keyword: '%s'", $keyword));

                $keywordData = $analytics->firstWhere('keys.0', $keyword);

                if ($keywordData) {
                    $this->info(sprintf("Found data for keyword '%s':", $keyword));
                    $this->table(
                        ['Metric', 'Value'],
                        [
                            ['Clicks', $keywordData['clicks'] ?? 0],
                            ['Impressions', $keywordData['impressions'] ?? 0],
                            ['CTR', round(($keywordData['ctr'] ?? 0) * 100, 2) . '%'],
                            ['Average Position', round($keywordData['position'] ?? 0, 1)],
                        ]
                    );
                } else {
                    $this->warn(sprintf("No data found for keyword '%s' in this date range.", $keyword));
                }
            }

            // Log raw data for debugging
            Log::channel('daily')->info('Search Console Debug - Raw API Response', [
                'project_id' => $projectId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'property_url' => $propertyUrl,
                'total_results' => $analytics->count(),
                'first_10_results' => $analytics->take(10)->toArray(),
            ]);

            $this->newLine();
            $this->info('✨ Debug data has been logged to: storage/logs/laravel-' . now()->format('Y-m-d') . '.log');
            $this->newLine();
            $this->warn('📝 To compare with Search Console web interface:');
            $this->line('1. Go to: https://search.google.com/search-console');
            $this->line('2. Select property: ' . $propertyUrl);
            $this->line('3. Go to Performance > Search results');
            $this->line(sprintf('4. Set date range: %s to %s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d')));
            $this->line("5. Check 'Queries' tab");
            $this->line('6. Compare the numbers with the API results above');
        } catch (Exception $exception) {
            $this->error('Error: ' . $exception->getMessage());
            Log::error('Search Console Debug - Error', [
                'project_id' => $projectId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return 1;
        }

        return 0;
    }
}
