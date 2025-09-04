<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleSearchConsole extends Command
{
    protected $signature = 'gsc:sync 
                            {--project= : Specific project ID to sync}
                            {--all : Sync all projects}';

    protected $description = 'Sync Google Search Console data for projects';

    public function __construct(
        private readonly GoogleSearchConsoleService $searchConsoleService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->searchConsoleService->hasCredentials()) {
            $this->error('Google Search Console credentials are not configured.');
            $this->warn('Please set up your Google Cloud credentials in the config/services.php file.');

            return Command::FAILURE;
        }

        if ($projectId = $this->option('project')) {
            $projects = Project::where('id', $projectId)->get();
            if ($projects->isEmpty()) {
                $this->error("Project with ID {$projectId} not found.");

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
        $this->withProgressBar($projects, function (Project $project) {
            try {
                $this->line(" Syncing project: {$project->name}");

                $importedCount = $this->searchConsoleService->importAndUpdateRankings($project);

                $this->line(" ✓ Imported {$importedCount} ranking entries for {$project->name}");

                Log::info('GSC sync completed', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'imported_count' => $importedCount,
                ]);
            } catch (\Exception $e) {
                $this->error(" ✗ Failed to sync {$project->name}: {$e->getMessage()}");

                Log::error('GSC sync failed', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        $this->newLine();
        $this->info('Google Search Console sync completed!');

        return Command::SUCCESS;
    }
}
