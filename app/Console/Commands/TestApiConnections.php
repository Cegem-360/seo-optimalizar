<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
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
            $project = Project::find($projectId);
            if (!$project) {
                $this->error("Project with ID {$projectId} not found.");
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
        $this->line("Testing project: <info>{$project->name}</info> (ID: {$project->id})");
        $this->line("URL: {$project->url}");
        $this->newLine();

        try {
            $apiManager = ApiServiceManager::forProject($project);
            $results = $apiManager->testAllConnections();

            $table = [];
            foreach ($results as $service => $result) {
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

            $this->info("Configured services: {$configuredCount}/{$totalCount}");

            if ($configuredCount === 0) {
                $this->warn('No API services are configured for this project.');
                $this->line('Please add API credentials in the Filament admin panel.');
            }

        } catch (\Exception $e) {
            $this->error("Error testing project {$project->name}: " . $e->getMessage());
        }
    }
}
