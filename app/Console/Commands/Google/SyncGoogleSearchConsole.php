<?php

declare(strict_types=1);

namespace App\Console\Commands\Google;

use App\Models\Project;
use App\Services\GoogleSearchConsoleService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncGoogleSearchConsole extends Command
{
    protected $signature = 'gsc:sync 
                            {--project= : Specific project ID to sync}
                            {--all : Sync all projects}';

    protected $description = 'Sync Google Search Console data for projects';

    public function __construct(
        private readonly GoogleSearchConsoleService $googleSearchConsoleService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->googleSearchConsoleService->hasCredentials()) {
            $this->error('Google Search Console credentials are not configured.');
            $this->warn('Please set up your Google Cloud credentials in the config/services.php file.');

            return Command::FAILURE;
        }

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

                $importedCount = $this->googleSearchConsoleService->importAndUpdateRankings($project);

                $this->line(sprintf(' ✓ Imported %s ranking entries for %s', $importedCount, $project->name));
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
