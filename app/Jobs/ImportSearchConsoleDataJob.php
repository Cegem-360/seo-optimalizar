<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Project;
use App\Services\GoogleSearchConsoleService;
use Exception;
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
            if (! $googleSearchConsoleService->hasCredentials()) {
                Log::error('Google Search Console credentials not configured');

                return;
            }

            $importedCount = $googleSearchConsoleService->importAndUpdateRankings($this->project);
        } catch (Exception $exception) {
            Log::error(sprintf('Search Console import failed for project %s: ', $this->project->id) . $exception->getMessage());
            throw $exception;
        }
    }
}
