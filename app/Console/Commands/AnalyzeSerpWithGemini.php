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
    public function handle()
    {
        $projectId = $this->argument('project');
        $keywordFilter = $this->argument('keyword');
        $limit = (int) $this->option('limit');
        
        if ($projectId) {
            $projects = [Project::findOrFail($projectId)];
            $this->info("Analyzing SERP for project: {$projects[0]->name}");
        } else {
            $projects = Project::all();
            $this->info('Analyzing SERP for all projects');
        }
        
        $totalAnalyzed = 0;
        
        foreach ($projects as $project) {
            $this->info("\nProcessing project: {$project->name} (ID: {$project->id})");
            
            try {
                $manager = ApiServiceManager::forProject($project);
                
                if (!$manager->hasService('gemini')) {
                    $this->warn("Google Gemini not configured for project: {$project->name}");
                    continue;
                }
                
                if (!$manager->hasService('serpapi')) {
                    $this->warn("SerpAPI not configured for project: {$project->name} (needed for SERP data)");
                    continue;
                }
                
                $gemini = $manager->getGemini();
                
                // Kulcsszavak lekérése
                $keywordsQuery = $project->keywords();
                
                if ($keywordFilter) {
                    $keywordsQuery->where('keyword', 'like', "%{$keywordFilter}%");
                }
                
                $keywords = $keywordsQuery->limit($limit)->get();
                
                if ($keywords->isEmpty()) {
                    $this->warn("No keywords found for project: {$project->name}");
                    continue;
                }
                
                $this->info("Found {$keywords->count()} keywords to analyze");
                
                foreach ($keywords as $keyword) {
                    $this->line("Analyzing keyword: {$keyword->keyword}");
                    
                    try {
                        $analysis = $gemini->analyzeKeywordPosition($keyword);
                        
                        if ($analysis) {
                            $this->displayAnalysisResults($keyword, $analysis);
                            $totalAnalyzed++;
                            
                            // Rate limiting
                            sleep(2);
                        } else {
                            $this->warn("Could not analyze keyword: {$keyword->keyword}");
                        }
                        
                    } catch (\Exception $e) {
                        $this->error("Error analyzing {$keyword->keyword}: {$e->getMessage()}");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("Error processing project {$project->name}: {$e->getMessage()}");
            }
        }
        
        $this->info("\nTotal keywords analyzed: {$totalAnalyzed}");
        
        if ($totalAnalyzed === 0) {
            $this->warn('No keywords were analyzed. Make sure:');
            $this->warn('1. Google Gemini API key is configured');
            $this->warn('2. SerpAPI is configured (for SERP data)');
            $this->warn('3. Projects have keywords');
        }
        
        return Command::SUCCESS;
    }
    
    private function displayAnalysisResults(Keyword $keyword, array $analysis): void
    {
        $this->info("\n✓ Analysis for: {$keyword->keyword}");
        
        $this->line("Competition Level: " . ucfirst($analysis['competition_level'] ?? 'unknown'));
        $this->line("Search Intent: " . ucfirst($analysis['search_intent'] ?? 'unknown'));
        
        if (!empty($analysis['dominant_content_types'])) {
            $this->line("Content Types: " . implode(', ', $analysis['dominant_content_types']));
        }
        
        if (!empty($analysis['opportunities'])) {
            $this->line("Opportunities:");
            foreach ($analysis['opportunities'] as $opportunity) {
                $this->line("  - {$opportunity}");
            }
        }
        
        if (!empty($analysis['challenges'])) {
            $this->line("Challenges:");
            foreach ($analysis['challenges'] as $challenge) {
                $this->line("  - {$challenge}");
            }
        }
        
        if (!empty($analysis['optimization_tips'])) {
            $this->line("Optimization Tips:");
            foreach ($analysis['optimization_tips'] as $tip) {
                $this->line("  - {$tip}");
            }
        }
        
        if (!empty($analysis['summary'])) {
            $this->line("Summary: {$analysis['summary']}");
        }
        
        $this->line(str_repeat('-', 50));
    }
}
