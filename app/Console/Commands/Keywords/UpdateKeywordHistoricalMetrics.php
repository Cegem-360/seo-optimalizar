<?php

declare(strict_types=1);

namespace App\Console\Commands\Keywords;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Console\Command;

class UpdateKeywordHistoricalMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:update-keywords-historical {project?} {--batch-size=5} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update keyword historical metrics data from Google Ads Historical Metrics API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->argument('project');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        if ($projectId) {
            $projects = [Project::query()->findOrFail($projectId)];
            $this->info('Updating historical metrics for project: ' . $projects[0]->name);
        } else {
            $projects = Project::all();
            $this->info('Updating historical metrics for all projects');
        }

        if (! $force) {
            $this->warn('Historical metrics API calls are more expensive. Use --force to confirm.');
            $this->info('This will use Google Ads API credits for detailed historical data.');

            if (! $this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $totalUpdated = 0;

        foreach ($projects as $project) {
            $this->info(sprintf('Processing project: %s (ID: %s)', $project->name, $project->id));

            try {
                $manager = ApiServiceManager::forProject($project);

                if (! $manager->hasService('google_ads')) {
                    $this->warn('Google Ads service not configured for project: ' . $project->name);

                    continue;
                }

                $googleAds = $manager->getGoogleAds();
                $updated = $googleAds->updateProjectKeywordsWithHistoricalMetrics($batchSize);

                $this->info(sprintf('Updated %d keywords with historical metrics for %s', $updated, $project->name));
                $totalUpdated += $updated;
            } catch (Exception $e) {
                $this->error(sprintf('Error processing project %s: %s', $project->name, $e->getMessage()));
            }
        }

        $this->info('Total keywords updated with historical metrics: ' . $totalUpdated);

        if ($totalUpdated === 0) {
            $this->warn('No keywords were updated. Make sure:');
            $this->warn('1. Google Ads API credentials are configured');
            $this->warn('2. Projects have keywords that need historical metrics updates');
            $this->warn('3. API service is working correctly');
        }

        return self::SUCCESS;
    }
}
