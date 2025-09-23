<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportSearchConsoleDataJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project,
    ) {}

    public function handle(): void
    {
        try {
            $apiManager = new ApiServiceManager($this->project);

            if (! $apiManager->hasService('google_search_console')) {
                Log::warning("Google Search Console not configured for project: {$this->project->name}");

                return;
            }

            $gscService = $apiManager->getGoogleSearchConsole();
            $importedCount = $gscService->importKeywords();

            Log::info("Search Console import completed for project {$this->project->name}: {$importedCount} keywords imported");
        } catch (Exception $exception) {
            Log::error(sprintf('Search Console import failed for project %s: ', $this->project->id) . $exception->getMessage());
            throw $exception;
        }
    }
}
