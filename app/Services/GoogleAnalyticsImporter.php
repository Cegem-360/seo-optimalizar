<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;
use InvalidArgumentException;

class GoogleAnalyticsImporter
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
    ) {}

    /**
     * Import the provided analytics data for a project
     */
    public function importAnalyticsData(Project $project, array $analyticsData, ?Carbon $date = null): void
    {
        $date ??= Carbon::yesterday();

        // Validate the data structure
        $this->validateAnalyticsData($analyticsData);

        // Store the analytics report
        $this->analyticsService->storeAnalyticsReport($project, $analyticsData, $date);
    }

    /**
     * Example method to import the JSON data you provided
     */
    public function importFromJson(Project $project, string $jsonData, ?Carbon $date = null): void
    {
        $data = json_decode($jsonData, true);

        if (! $data) {
            throw new InvalidArgumentException('Invalid JSON data provided');
        }

        $this->importAnalyticsData($project, $data, $date);
    }

    /**
     * Validate the analytics data structure
     */
    private function validateAnalyticsData(array $data): void
    {
        $requiredKeys = ['overview'];

        foreach ($requiredKeys as $requiredKey) {
            if (! isset($data[$requiredKey])) {
                throw new InvalidArgumentException('Missing required key: ' . $requiredKey);
            }
        }

        // Validate overview structure
        $overview = $data['overview'];
        $overviewKeys = ['sessions', 'activeUsers', 'totalUsers', 'newUsers', 'bounceRate', 'averageSessionDuration', 'screenPageViews', 'conversions'];

        foreach ($overviewKeys as $overviewKey) {
            if (! isset($overview[$overviewKey])) {
                throw new InvalidArgumentException('Missing required overview key: ' . $overviewKey);
            }
        }
    }

    /**
     * Example usage method showing how to use the system
     */
    public static function exampleUsage(): string
    {
        return '
To use this analytics system:

1. Run the migration:
   php artisan migrate

2. Import analytics data manually:
   $project = Project::first();
   $analyticsData = [/* your JSON data */];
   $importer = new GoogleAnalyticsImporter(new AnalyticsService());
   $importer->importAnalyticsData($project, $analyticsData);

3. Or use the console command:
   php artisan analytics:collect-daily

4. Schedule daily collection in app/Console/Kernel.php:
   $schedule->command("analytics:collect-daily")->dailyAt("01:00");

5. Query analytics data:
   $project = Project::first();
   $service = new AnalyticsService();
   $latestReport = $service->getLatestReport($project);
   $trends = $service->getMonthlyTrends($project);
   $topPages = $service->getTopPerformingPages($project);
        ';
    }
}
