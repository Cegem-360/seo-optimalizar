<?php

namespace App\Console\Commands;

use App\Models\Keyword;
use App\Models\Project;
use App\Services\Api\ApiServiceManager;
use Illuminate\Console\Command;

class AnalyzeSerpWithGemini extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:analyze-serp {project?} {keyword?} {--limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze SERP results with Google Gemini AI to get insights on keyword positions and competition';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projectId = $this->argument('project');
        $keywordFilter = $this->argument('keyword');
        $limit = (int) $this->option('limit');

        if ($projectId) {
            $projects = [\App\Models\Project::query()->findOrFail($projectId)];
            $this->info('Analyzing SERP for project: ' . $projects[0]->name);
        } else {
            $projects = Project::all();
            $this->info('Analyzing SERP for all projects');
        }

        $totalAnalyzed = 0;

        foreach ($projects as $project) {
            $this->info("\nProcessing project: {$project->name} (ID: {$project->id})");

            try {
                $manager = ApiServiceManager::forProject($project);

                if (! $manager->hasService('gemini')) {
                    $this->warn('Google Gemini not configured for project: ' . $project->name);

                    continue;
                }

                $gemini = $manager->getGemini();

                // Kulcsszavak lekérése
                $keywordsQuery = $project->keywords();

                if ($keywordFilter) {
                    $keywordsQuery->where('keyword', 'like', sprintf('%%%s%%', $keywordFilter));
                }

                $keywords = $keywordsQuery->limit($limit)->get();

                if ($keywords->isEmpty()) {
                    $this->warn('No keywords found for project: ' . $project->name);

                    continue;
                }

                $this->info(sprintf('Found %s keywords to analyze', $keywords->count()));

                foreach ($keywords as $keyword) {
                    $this->line('Analyzing keyword: ' . $keyword->keyword);

                    try {
                        $analysis = $gemini->analyzeKeywordPosition($keyword);

                        if ($analysis !== null && $analysis !== []) {
                            $this->displayAnalysisResults($keyword, $analysis);
                            $totalAnalyzed++;

                            // Rate limiting
                            sleep(2);
                        } else {
                            $this->warn('Could not analyze keyword: ' . $keyword->keyword);
                        }
                    } catch (\Exception $e) {
                        $this->error(sprintf('Error analyzing %s: %s', $keyword->keyword, $e->getMessage()));
                    }
                }
            } catch (\Exception $e) {
                $this->error(sprintf('Error processing project %s: %s', $project->name, $e->getMessage()));
            }
        }

        $this->info('
Total keywords analyzed: ' . $totalAnalyzed);

        if ($totalAnalyzed === 0) {
            $this->warn('No keywords were analyzed. Make sure:');
            $this->warn('1. Google Gemini API key is configured');
            $this->warn('2. Projects have keywords with ranking data');
            $this->warn('3. Keywords have recent ranking entries from Search Console');
        }

        return Command::SUCCESS;
    }

    private function displayAnalysisResults(Keyword $keyword, array $analysis): void
    {
        $this->info('
✓ Analysis for: ' . $keyword->keyword);

        $this->line('Competition Level: ' . ucfirst($analysis['competition_level'] ?? 'unknown'));
        $this->line('Search Intent: ' . ucfirst($analysis['search_intent'] ?? 'unknown'));

        if (! empty($analysis['dominant_content_types'])) {
            $this->line('Content Types: ' . implode(', ', $analysis['dominant_content_types']));
        }

        if (! empty($analysis['opportunities'])) {
            $this->line('Opportunities:');
            foreach ($analysis['opportunities'] as $opportunity) {
                $this->line('  - ' . $opportunity);
            }
        }

        if (! empty($analysis['challenges'])) {
            $this->line('Challenges:');
            foreach ($analysis['challenges'] as $challenge) {
                $this->line('  - ' . $challenge);
            }
        }

        if (! empty($analysis['optimization_tips'])) {
            $this->line('Optimization Tips:');
            foreach ($analysis['optimization_tips'] as $tip) {
                $this->line('  - ' . $tip);
            }
        }

        if (! empty($analysis['summary'])) {
            $this->line('Summary: ' . $analysis['summary']);
        }

        $this->line(str_repeat('-', 50));
    }
}
