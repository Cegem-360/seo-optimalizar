<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportSearchConsoleDataJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Project $project
    ) {}

    public function handle(GoogleSearchConsoleService $googleSearchConsoleService): void
    {
        try {
            // Log::info('Starting Search Console import for project: ' . $this->project->name);

            if (! $googleSearchConsoleService->hasCredentials()) {
                Log::error('Google Search Console credentials not configured');

                return;
            }

            $importedCount = $googleSearchConsoleService->importAndUpdateRankings($this->project);

            // Log::info(sprintf('Successfully imported %d ranking records for project: %s', $importedCount, $this->project->name));
        } catch (\Exception $exception) {
            Log::error(sprintf('Search Console import failed for project %s: ', $this->project->id) . $exception->getMessage());
            throw $exception;
        }
    }
}
