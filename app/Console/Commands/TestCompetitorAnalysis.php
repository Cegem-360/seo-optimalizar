<?php

namespace App\Console\Commands;

use App\Models\Keyword;
use App\Models\Project;
use App\Services\Api\CompetitorAnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class TestCompetitorAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:competitor-analysis {keyword_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AI-powered competitor discovery and analysis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing AI-powered Competitor Analysis...');

        // Lekérjük a kulcsszót
        $keywordId = $this->argument('keyword_id');

        if ($keywordId) {
            $keyword = Keyword::query()->find($keywordId);
        } else {
            // Ha nincs megadva, kérjük le az első elérhetőt
            $keyword = Keyword::query()->whereHas('project')->first();
        }

        if (! $keyword) {
            $this->error('No keyword found!');

            return 1;
        }

        /** @var Keyword $keyword */
        /** @var Project $project */
        $project = $keyword->project;
        $this->info(sprintf('Using keyword: %s (ID: %s)', $keyword->keyword, $keyword->id));
        $this->info('Project: ' . $project->name);
        $this->newLine();

        // CompetitorAnalysisService inicializálása
        $competitorAnalysisService = new CompetitorAnalysisService($project);

        // Versenytársak felfedezése és elemzése
        $this->info('Discovering competitors with AI...');
        $competitors = $competitorAnalysisService->analyzeTopCompetitors($keyword, 5);

        if ($competitors === []) {
            $this->warn('No competitors found or analysis failed.');

            return 1;
        }

        $this->info(sprintf('Found and analyzed %d competitors:', count($competitors)));
        $this->newLine();

        // Eredmények megjelenítése
        foreach ($competitors as $competitor) {
            $this->displayCompetitorAnalysis($competitor);
        }

        // AI-val felfedezett versenytársak száma
        $aiDiscovered = (new Collection($competitors))->where('ai_discovered', true)->count();
        $this->info(sprintf('AI discovered: %d competitors', $aiDiscovered));

        return 0;
    }

    private function displayCompetitorAnalysis($analysis): void
    {
        $this->info(sprintf('--- %s ---', $analysis->competitor_domain));
        $this->line('Position: ' . $analysis->position);
        $this->line('URL: ' . $analysis->competitor_url);

        if ($analysis->ai_discovered) {
            $this->comment('[AI DISCOVERED]');
        }

        if ($analysis->competitor_type) {
            $this->line('Type: ' . $analysis->competitor_type);
        }

        if ($analysis->strength_score) {
            $color = $analysis->strength_score >= 7 ? 'error' : ($analysis->strength_score >= 4 ? 'warn' : 'info');
            $this->line(sprintf('Strength Score: <%s>%s/10</%s>', $color, $analysis->strength_score, $color));
        }

        if ($analysis->relevance_reason) {
            $this->line('Relevance: ' . $analysis->relevance_reason);
        }

        if ($analysis->estimated_traffic) {
            $this->line('Traffic: ' . $analysis->estimated_traffic);
        }

        if ($analysis->main_advantages && is_array($analysis->main_advantages)) {
            $this->line('Main Advantages:');
            foreach ($analysis->main_advantages as $advantage) {
                $this->line('  - ' . $advantage);
            }
        }

        // Technikai adatok
        $this->line(sprintf(
            'Technical: DA:%s | PA:%s | Speed:%s | SSL:%s | Mobile:%s',
            $analysis->domain_authority ?? 'N/A',
            $analysis->page_authority ?? 'N/A',
            $analysis->page_speed_score ?? 'N/A',
            $analysis->has_ssl ? 'Yes' : 'No',
            $analysis->is_mobile_friendly ? 'Yes' : 'No'
        ));

        $this->newLine();
    }
}
