<?php

declare(strict_types=1);

namespace App\Console\Commands\Testing;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Console\Command;

class TestApiConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:test-api {project? : Project ID to test (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test API connections for all projects or a specific project';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing API connections...');

        $projectId = $this->argument('project');

        if ($projectId) {
            $project = Project::query()->find($projectId);
            if (! $project) {
                $this->error(sprintf('Project with ID %s not found.', $projectId));

                return 1;
            }

            $this->testProject($project);
        } else {
            $projects = Project::all();

            if ($projects->isEmpty()) {
                $this->warn('No projects found in the database.');

                return 0;
            }

            foreach ($projects as $project) {
                $this->testProject($project);
                $this->newLine();
            }
        }

        return 0;
    }

    private function testProject(Project $project): void
    {
        $this->line(sprintf('Testing project: <info>%s</info> (ID: %s)', $project->name, $project->id));
        $this->line('URL: ' . $project->url);
        $this->newLine();

        try {
            $apiManager = ApiServiceManager::forProject($project);
            $results = $apiManager->testAllConnections();

            $table = [];
            foreach ($results as $result) {
                $status = $result['success'] ? '✅ Connected' : '❌ Failed';
                $table[] = [
                    $result['name'],
                    $status,
                    $result['message'],
                ];
            }

            $this->table(['Service', 'Status', 'Message'], $table);

            // Show configured services summary
            $configuredServices = $apiManager->getConfiguredServices();
            $configuredCount = $configuredServices->where('configured', true)->count();
            $totalCount = $configuredServices->count();

            $this->info(sprintf('Configured services: %d/%d', $configuredCount, $totalCount));

            if ($configuredCount === 0) {
                $this->warn('No API services are configured for this project.');
                $this->line('Please add API credentials in the Filament admin panel.');
            }
        } catch (Exception $exception) {
            $this->error(sprintf('Error testing project %s: ', $project->name) . $exception->getMessage());
        }
    }
}
