<?php

namespace App\Console\Commands;

use App\Jobs\ImportSearchConsoleDataJob;
use App\Models\Project;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckKeywordPositions extends Command
{
    protected $signature = 'seo:check-positions {--project= : Check positions for specific project ID}';

    protected $description = 'Check keyword positions for all projects or a specific project';

    public function handle(): int
    {
        $this->info('Starting keyword position check...');

        $projects = $this->option('project')
            ? Project::query()->where('id', $this->option('project'))->get()
            : Project::all();

        if ($projects->isEmpty()) {
            $this->error('No projects found to check positions for.');

            return self::FAILURE;
        }

        $totalDispatched = 0;

        foreach ($projects as $project) {
            $this->info('Dispatching position check job for project: ' . $project->name);

            try {
                ImportSearchConsoleDataJob::dispatch($project);
                $totalDispatched++;

                $this->info('✓ Job dispatched for ' . $project->name);
            } catch (Exception $e) {
                $this->error(sprintf('✗ Error dispatching job for %s: ', $project->name) . $e->getMessage());
                Log::error(sprintf('Position check job dispatch error for project %s: ', $project->id) . $e->getMessage());
            }
        }

        $this->info('Position check completed! Total jobs dispatched: ' . $totalDispatched);

        return self::SUCCESS;
    }
}
