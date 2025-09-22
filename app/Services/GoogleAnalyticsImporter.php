<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;

class GoogleAnalyticsImporter
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Import the provided analytics data for a project
     */
    public function importAnalyticsData(Project $project, array $analyticsData, Carbon $date = null): void
    {
        $date = $date ?? Carbon::yesterday();

        // Validate the data structure
        $this->validateAnalyticsData($analyticsData);

        // Store the analytics report
        $this->analyticsService->storeAnalyticsReport($project, $analyticsData, $date);
    }

    /**
     * Example method to import the JSON data you provided
     */
    public function importFromJson(Project $project, string $jsonData, Carbon $date = null): void
    {
        $data = json_decode($jsonData, true);

        if (!$data) {
            throw new \InvalidArgumentException('Invalid JSON data provided');
        }

        $this->importAnalyticsData($project, $data, $date);
    }

    /**
     * Validate the analytics data structure
     */
    private function validateAnalyticsData(array $data): void
    {
        $requiredKeys = ['overview'];
        $optionalKeys = ['traffic_sources', 'top_pages', 'user_demographics', 'device_data', 'conversion_data', 'real_time'];

        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new \InvalidArgumentException("Missing required key: {$key}");
            }
        }

        // Validate overview structure
        $overview = $data['overview'];
        $overviewKeys = ['sessions', 'activeUsers', 'totalUsers', 'newUsers', 'bounceRate', 'averageSessionDuration', 'screenPageViews', 'conversions'];

        foreach ($overviewKeys as $key) {
            if (!isset($overview[$key])) {
                throw new \InvalidArgumentException("Missing required overview key: {$key}");
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