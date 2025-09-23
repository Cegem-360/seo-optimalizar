<?php

declare(strict_types=1);

namespace App\Console\Commands\Google;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportSearchConsoleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:import-search-console {--project= : Import for specific project ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import SEO data from Google Search Console';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Google Search Console data import...');

        // Get projects to import for
        $projects = $this->option('project')
            ? Project::query()->where('id', $this->option('project'))->get()
            : Project::all();

        if ($projects->isEmpty()) {
            $this->error('No projects found to import data for.');

            return self::FAILURE;
        }

        $totalImported = 0;

        foreach ($projects as $project) {
            $this->info('Importing data for project: ' . $project->name);

            try {
                $apiManager = new ApiServiceManager($project);

                if (! $apiManager->hasService('google_search_console')) {
                    $this->warn("Google Search Console not configured for project: {$project->name}");

                    continue;
                }

                $gscService = $apiManager->getGoogleSearchConsole();
                $importedCount = $gscService->importKeywords();
                $totalImported += $importedCount;

                $this->info(sprintf('✓ Imported %d keywords for %s', $importedCount, $project->name));
            } catch (Exception $e) {
                $this->error(sprintf('✗ Error importing data for %s: ', $project->name) . $e->getMessage());
                Log::error(sprintf('Search Console import error for project %s: ', $project->id) . $e->getMessage());
            }
        }

        $this->info('Import completed! Total keywords imported: ' . $totalImported);

        return self::SUCCESS;
    }
}
