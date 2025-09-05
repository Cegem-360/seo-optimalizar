<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Illuminate\Console\Command;
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
            ? collect([Project::find($projectId)])->filter()
            : $this->getMonitorableProjects();
            
        if ($projects->isEmpty()) {
            $this->warn('No projects found with PageSpeed API credentials.');
            return 0;
        }
        
        $this->info("Found {$projects->count()} project(s) to monitor.");
        $this->newLine();
        
        $results = [];
        
        foreach ($projects as $project) {
            $results[] = $this->monitorProject($project, $strategy, $force);
        }
        
        // Summary
        $successful = collect($results)->where('success', true)->count();
        $total = count($results);
        
        $this->newLine();
        $this->info("✅ Monitoring completed: {$successful}/{$total} projects analyzed successfully.");
        
        if ($successful < $total) {
            $errors = collect($results)->where('success', false)->pluck('error');
            $this->warn('Errors occurred:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }
        
        return $successful === $total ? 0 : 1;
    }
    
    private function getMonitorableProjects()
    {
        return Project::whereHas('apiCredentials', function ($query) {
            $query->where('service', 'google_pagespeed_insights')
                  ->where('is_active', true);
        })->get();
    }
    
    private function monitorProject(Project $project, string $strategy, bool $force): array
    {
        $this->line("📊 Analyzing: <info>{$project->name}</info> ({$project->url})");
        
        try {
            // Check if we should skip based on recent analysis
            if (!$force && $this->wasRecentlyAnalyzed($project, $strategy)) {
                $this->line("  ⏭️  Skipped - analyzed within the last 2 hours");
                return ['success' => true, 'skipped' => true];
            }
            
            $manager = ApiServiceManager::forProject($project);
            $pageSpeed = $manager->getPageSpeedInsights();
            
            if (!$pageSpeed->isConfigured()) {
                $error = "PageSpeed API not configured";
                $this->line("  ❌ {$error}");
                return ['success' => false, 'error' => $error];
            }
            
            $strategies = $strategy === 'both' ? ['mobile', 'desktop'] : [$strategy];
            $analyzed = [];
            
            foreach ($strategies as $currentStrategy) {
                $this->line("  🔄 Running {$currentStrategy} analysis...");
                
                $results = $pageSpeed->analyzeProjectUrl($currentStrategy);
                $analyzed[] = $currentStrategy;
                
                $score = $results['scores']['performance'] ?? 0;
                $this->line("  ✅ {$currentStrategy} completed - Performance: {$score}/100");
                
                // Small delay between requests to respect rate limits
                if (count($strategies) > 1) {
                    sleep(2);
                }
            }
            
            Log::info('PageSpeed monitoring completed', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'strategies' => $analyzed,
            ]);
            
            return ['success' => true, 'analyzed' => $analyzed];
            
        } catch (\Exception $e) {
            $error = "Error analyzing {$project->name}: " . $e->getMessage();
            $this->line("  ❌ {$error}");
            
            Log::error('PageSpeed monitoring failed', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'error' => $e->getMessage(),
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
                
            if (!$recentAnalysis) {
                return false; // At least one strategy hasn't been analyzed recently
            }
        }
        
        return true; // All requested strategies were analyzed recently
    }
}
