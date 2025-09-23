<?php

declare(strict_types=1);

namespace App\Console\Commands\Google;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

class TestGoogleConnection extends Command
{
    protected $signature = 'google:test-connection {--project= : Test for specific project ID}';

    protected $description = 'Test Google Search Console API connection for projects';

    /**
     * Create a new console command instance.
     */
    public function __construct(private readonly Repository $repository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Testing Google Search Console API connection...');
        $this->newLine();

        // Get projects to test
        $projects = $this->option('project')
            ? Project::query()->where('id', $this->option('project'))->get()
            : Project::all();

        if ($projects->isEmpty()) {
            $this->error('No projects found to test.');

            return self::FAILURE;
        }

        $successCount = 0;
        $totalCount = $projects->count();

        foreach ($projects as $project) {
            $this->info("Testing project: {$project->name}");

            try {
                $apiManager = new ApiServiceManager($project);

                if (! $apiManager->hasService('google_search_console')) {
                    $this->warn("✗ Google Search Console not configured for project: {$project->name}");

                    continue;
                }

                $gscService = $apiManager->getGoogleSearchConsole();
                $isConnected = $gscService->testConnection();

                if ($isConnected) {
                    $this->info("✓ Connection successful for project: {$project->name}");
                    $successCount++;

                    // Try to get sites
                    $sites = $gscService->getSites();
                    $this->info("  Available sites: {$sites->count()}");
                } else {
                    $this->error("✗ Connection failed for project: {$project->name}");
                }
            } catch (Exception $exception) {
                $this->error("✗ Error testing project {$project->name}: {$exception->getMessage()}");
            }

            $this->newLine();
        }

        $this->info("Test completed: {$successCount}/{$totalCount} projects connected successfully");

        return $successCount > 0 ? self::SUCCESS : self::FAILURE;
    }
}
