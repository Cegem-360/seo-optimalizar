<?php

namespace App\Console\Commands\PageSpeed;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PageSpeedMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:pagespeed-monitor {--project= : Specific project ID to monitor} {--strategy=mobile : Strategy to use (mobile|desktop|both)} {--force : Force monitoring even if recently analyzed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically monitor PageSpeed for all projects with API credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting PageSpeed monitoring...');

        $projectId = $this->option('project');
        $strategy = $this->option('strategy');
        $force = $this->option('force');

        $projects = $projectId
            ? (new Collection([Project::query()->find($projectId)]))->filter()
            : $this->getMonitorableProjects();

        if ($projects->isEmpty()) {
            $this->warn('No projects found with PageSpeed API credentials.');

            return 0;
        }

        $this->info(sprintf('Found %s project(s) to monitor.', $projects->count()));
        $this->newLine();

        $results = [];

        foreach ($projects as $project) {
            $results[] = $this->monitorProject($project, $strategy, $force);
        }

        // Summary
        $successful = (new Collection($results))->where('success', true)->count();
        $total = count($results);

        $this->newLine();
        $this->info(sprintf('✅ Monitoring completed: %d/%d projects analyzed successfully.', $successful, $total));

        if ($successful < $total) {
            $errors = (new Collection($results))->where('success', false)->pluck('error');
            $this->warn('Errors occurred:');
            foreach ($errors as $error) {
                $this->line('  - ' . $error);
            }
        }

        return $successful === $total ? 0 : 1;
    }

    private function getMonitorableProjects()
    {
        return Project::query()->whereHas('apiCredentials', function ($query): void {
            $query->where('service', 'google_pagespeed_insights')
                ->where('is_active', true);
        })->get();
    }

    private function monitorProject(Project $project, string $strategy, bool $force): array
    {
        $this->line(sprintf('📊 Analyzing: <info>%s</info> (%s)', $project->name, $project->url));

        try {
            // Check if we should skip based on recent analysis
            if (! $force && $this->wasRecentlyAnalyzed($project, $strategy)) {
                $this->line('  ⏭️  Skipped - analyzed within the last 2 hours');

                return ['success' => true, 'skipped' => true];
            }

            $manager = ApiServiceManager::forProject($project);
            $pageSpeed = $manager->getPageSpeedInsights();

            if (! $pageSpeed->isConfigured()) {
                $error = 'PageSpeed API not configured';
                $this->line('  ❌ ' . $error);

                return ['success' => false, 'error' => $error];
            }

            $strategies = $strategy === 'both' ? ['mobile', 'desktop'] : [$strategy];
            $analyzed = [];

            foreach ($strategies as $currentStrategy) {
                $this->line(sprintf('  🔄 Running %s analysis...', $currentStrategy));

                $results = $pageSpeed->analyzeProjectUrl($currentStrategy);
                $analyzed[] = $currentStrategy;

                $score = $results['scores']['performance'] ?? 0;
                $this->line(sprintf('  ✅ %s completed - Performance: %s/100', $currentStrategy, $score));

                // Small delay between requests to respect rate limits
                if (count($strategies) > 1) {
                    sleep(2);
                }
            }

            return ['success' => true, 'analyzed' => $analyzed];
        } catch (Exception $exception) {
            $error = sprintf('Error analyzing %s: ', $project->name) . $exception->getMessage();
            $this->line('  ❌ ' . $error);

            Log::error('PageSpeed monitoring failed', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'error' => $exception->getMessage(),
            ]);

            return ['success' => false, 'error' => $error];
        }
    }

    private function wasRecentlyAnalyzed(Project $project, string $strategy): bool
    {
        $strategies = $strategy === 'both' ? ['mobile', 'desktop'] : [$strategy];

        foreach ($strategies as $currentStrategy) {
            $recentAnalysis = $project->pageSpeedResults()
                ->where('strategy', $currentStrategy)
                ->where('analyzed_at', '>=', now()->subHours(2))
                ->exists();

            if (! $recentAnalysis) {
                return false; // At least one strategy hasn't been analyzed recently
            }
        }

        return true; // All requested strategies were analyzed recently
    }
}
