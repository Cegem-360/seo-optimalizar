<?php

declare(strict_types=1);

namespace App\Console\Commands\Google;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleSearchConsole extends Command
{
    protected $signature = 'gsc:sync 
                            {--project= : Specific project ID to sync}
                            {--all : Sync all projects}';

    protected $description = 'Sync Google Search Console data for projects';

    public function handle(): int
    {
        if ($projectId = $this->option('project')) {
            $projects = Project::query()->where('id', $projectId)->get();
            if ($projects->isEmpty()) {
                $this->error(sprintf('Project with ID %s not found.', $projectId));

                return Command::FAILURE;
            }
        } elseif ($this->option('all')) {
            $projects = Project::all();
        } else {
            $this->error('Please specify --project=ID or --all option.');

            return Command::FAILURE;
        }

        if ($projects->isEmpty()) {
            $this->warn('No projects found to sync.');

            return Command::SUCCESS;
        }

        $this->info('Starting Google Search Console sync...');
        $this->withProgressBar($projects, function (Project $project): void {
            try {
                $this->line(' Syncing project: ' . $project->name);

                $apiManager = new ApiServiceManager($project);

                if (! $apiManager->hasService('google_search_console')) {
                    $this->warn("Google Search Console not configured for project: {$project->name}");

                    return;
                }

                $gscService = $apiManager->getGoogleSearchConsole();
                $syncedCount = $gscService->syncKeywordRankings();

                $this->line(sprintf(' ✓ Synced %s ranking entries for %s', $syncedCount, $project->name));
            } catch (Exception $exception) {
                $this->error(sprintf(' ✗ Failed to sync %s: %s', $project->name, $exception->getMessage()));

                Log::error('GSC sync failed', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'error' => $exception->getMessage(),
                ]);
            }
        });

        $this->newLine();
        $this->info('Google Search Console sync completed!');

        return Command::SUCCESS;
    }
}
