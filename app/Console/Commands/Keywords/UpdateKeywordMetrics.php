<?php

namespace App\Console\Commands\Keywords;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Console\Command;

class UpdateKeywordMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:update-keywords {project?} {--batch-size=20} {--service=google_ads} {--historical}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update keyword search volume and difficulty data from Google Ads Keyword Planner API (use --historical for detailed metrics)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->argument('project');
        $batchSize = (int) $this->option('batch-size');
        $service = $this->option('service');
        $useHistorical = $this->option('historical');

        if ($projectId) {
            $projects = [Project::query()->findOrFail($projectId)];
            $this->info('Updating keywords for project: ' . $projects[0]->name);
        } else {
            $projects = Project::all();
            $this->info('Updating keywords for all projects');
        }

        if ($useHistorical) {
            $this->warn('Using historical metrics - this provides more detailed data but uses more API credits.');
            if (! $this->confirm('Do you want to continue with historical metrics?')) {
                $this->info('Switching to regular keyword ideas API.');
                $useHistorical = false;
            }
        }

        $totalUpdated = 0;

        foreach ($projects as $project) {
            $this->info(sprintf('Processing project: %s (ID: %s)', $project->name, $project->id));

            try {
                $manager = ApiServiceManager::forProject($project);

                if ($service === 'google_ads' && $manager->hasService('google_ads')) {
                    $googleAds = $manager->getGoogleAds();

                    if ($useHistorical) {
                        // Use smaller batch size for historical metrics
                        $historicalBatchSize = min($batchSize, 5);
                        $updated = $googleAds->updateProjectKeywordsWithHistoricalMetrics($historicalBatchSize);
                        $this->info(sprintf('Updated %d keywords with historical metrics for %s', $updated, $project->name));
                    } else {
                        $updated = $googleAds->updateProjectKeywords($batchSize);
                        $this->info(sprintf('Updated %d keywords from Google Ads for %s', $updated, $project->name));
                    }

                    $totalUpdated += $updated;
                } else {
                    $this->warn('Google Ads service not configured for project: ' . $project->name);
                }
            } catch (Exception $e) {
                $this->error(sprintf('Error processing project %s: %s', $project->name, $e->getMessage()));
            }
        }

        $this->info('
Total keywords updated: ' . $totalUpdated);

        if ($totalUpdated === 0) {
            $this->warn('No keywords were updated. Make sure:');
            $this->warn('1. Google Ads API credentials are configured');
            $this->warn('2. Projects have keywords without search volume/difficulty data');
            $this->warn('3. API service is working correctly');
        }

        return Command::SUCCESS;
    }
}
