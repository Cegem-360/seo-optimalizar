<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Keyword;
use App\Services\Api\CompetitorAnalysisService;
use Exception;
use Illuminate\Console\Command;

class AnalyzeCompetitors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:analyze-competitors
                            {--project= : Specific project ID to analyze}
                            {--keyword= : Specific keyword ID to analyze}
                            {--limit=10 : Number of competitors to analyze per keyword}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze competitors for keywords and store results';

    public function __construct(private readonly CompetitorAnalysisService $competitorAnalysisService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting competitor analysis...');

        $keywords = $this->getKeywordsToAnalyze();
        $limit = (int) $this->option('limit');

        if ($keywords->isEmpty()) {
            $this->warn('No keywords found to analyze.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %s keywords to analyze.', $keywords->count()));

        $progressBar = $this->output->createProgressBar($keywords->count());
        $progressBar->start();

        $totalAnalyzed = 0;

        foreach ($keywords as $keyword) {
            $this->newLine();
            $this->info('Analyzing competitors for keyword: ' . $keyword->keyword);

            try {
                $competitors = $this->competitorAnalysisService->analyzeTopCompetitors($keyword, $limit);

                $this->info('  ✓ Analyzed ' . count($competitors) . ' competitors');
                $totalAnalyzed += count($competitors);
            } catch (Exception $e) {
                $this->error(sprintf('  ✗ Failed to analyze keyword %s: %s', $keyword->keyword, $e->getMessage()));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info('Competitor analysis completed! Total competitors analyzed: ' . $totalAnalyzed);

        return self::SUCCESS;
    }

    private function getKeywordsToAnalyze()
    {
        $query = Keyword::query();

        if ($keywordId = $this->option('keyword')) {
            return $query->where('id', $keywordId)->get();
        }

        if ($projectId = $this->option('project')) {
            $query->where('project_id', $projectId);
        }

        // Only analyze keywords that haven't been analyzed recently
        $query->whereDoesntHave('competitorAnalyses', function ($q): void {
            $q->where('analyzed_at', '>', now()->subDays(7));
        });

        return $query->with('project')->get();
    }
}
