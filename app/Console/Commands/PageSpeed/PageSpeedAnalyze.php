<?php

namespace App\Console\Commands\PageSpeed;

use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Exception;
use Illuminate\Console\Command;

class PageSpeedAnalyze extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:pagespeed {project? : Project ID to analyze} {--strategy=mobile : Strategy (mobile or desktop)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze PageSpeed Insights for a project';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->argument('project');
        $strategy = $this->option('strategy');

        if ($projectId) {
            $project = Project::query()->find($projectId);
            if (! $project) {
                $this->error(sprintf('Project with ID %s not found.', $projectId));

                return 1;
            }
        } else {
            // List available projects
            $projects = Project::all();
            if ($projects->isEmpty()) {
                $this->warn('No projects found.');

                return 0;
            }

            $this->info('Available projects:');
            foreach ($projects as $p) {
                $this->line(sprintf('  %s: %s (%s)', $p->id, $p->name, $p->url));
            }

            $projectId = $this->ask('Which project would you like to analyze?');
            $project = Project::query()->find($projectId);

            if (! $project) {
                $this->error('Invalid project ID.');

                return 1;
            }
        }

        $this->info(sprintf('Analyzing %s (%s) with %s strategy...', $project->name, $project->url, $strategy));

        try {
            $manager = ApiServiceManager::forProject($project);
            $pageSpeed = $manager->getPageSpeedInsights();

            if (! $pageSpeed->isConfigured()) {
                $this->error('PageSpeed Insights API is not configured for this project.');

                return 1;
            }

            $this->line('Running PageSpeed analysis...');
            $results = $pageSpeed->analyzeProjectUrl($strategy);

            // Display results
            $this->newLine();
            $this->info('📊 PageSpeed Results for ' . $project->url);
            $this->info('Strategy: ' . ucfirst($strategy));
            $this->newLine();

            if (isset($results['scores'])) {
                $scores = $results['scores'];
                $this->line('🚀 <info>Performance:</info> ' . $this->formatScore($scores['performance'] ?? 0));
                $this->line('♿ <info>Accessibility:</info> ' . $this->formatScore($scores['accessibility'] ?? 0));
                $this->line('✅ <info>Best Practices:</info> ' . $this->formatScore($scores['best_practices'] ?? 0));
                $this->line('🔍 <info>SEO:</info> ' . $this->formatScore($scores['seo'] ?? 0));
                $this->newLine();
            }

            // Core Web Vitals
            if (isset($results['core_web_vitals']) && ! empty($results['core_web_vitals'])) {
                $this->info('⚡ Core Web Vitals:');
                $vitals = $results['core_web_vitals'];

                if (isset($vitals['lcp'])) {
                    $this->line('  LCP (Largest Contentful Paint): ' . ($vitals['lcp']['display_value'] ?? 'N/A'));
                }

                if (isset($vitals['fcp'])) {
                    $this->line('  FCP (First Contentful Paint): ' . ($vitals['fcp']['display_value'] ?? 'N/A'));
                }

                if (isset($vitals['cls'])) {
                    $this->line('  CLS (Cumulative Layout Shift): ' . ($vitals['cls']['display_value'] ?? 'N/A'));
                }

                if (isset($vitals['speed_index'])) {
                    $this->line('  Speed Index: ' . ($vitals['speed_index']['display_value'] ?? 'N/A'));
                }
            }

            $this->newLine();
            $this->info('✅ Analysis completed successfully!');

            return 0;
        } catch (Exception $exception) {
            $this->error('Error analyzing PageSpeed: ' . $exception->getMessage());

            return 1;
        }
    }

    private function formatScore(?int $score): string
    {
        if ($score === null) {
            return 'N/A';
        }

        $color = match (true) {
            $score >= 90 => 'info',
            $score >= 50 => 'comment',
            default => 'error'
        };

        return sprintf('<%s>%d/100</%s>', $color, $score, $color);
    }
}
