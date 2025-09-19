<?php

namespace Database\Factories;

use App\Models\Keyword;
use App\Models\Project;
use App\Models\SerpAnalysisResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SerpAnalysisResult>
 */
class SerpAnalysisResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $position = fake()->numberBetween(1, 50);

        return [
            'project_id' => Project::factory(),
            'keyword_id' => Keyword::factory(),
            'search_id' => fake()->optional()->uuid(),
            'organic_results' => $this->generateOrganicResults(),
            'serp_metrics' => [
                'total_results' => fake()->numberBetween(100000, 10000000),
                'search_time' => fake()->randomFloat(2, 0.1, 2.0) . ' seconds',
                'device' => fake()->randomElement(['desktop', 'mobile']),
                'location' => fake()->randomElement(['google.com', 'google.hu', 'google.de']),
            ],
            'analysis_data' => [
                'position_rating' => fake()->randomElement(['kiváló', 'jó', 'közepes', 'gyenge', 'kritikus']),
                'current_position' => $position,
                'main_competitors' => [
                    fake()->domainName(),
                    fake()->domainName(),
                    fake()->domainName(),
                ],
                'competitor_advantages' => [
                    'Részletesebb tartalom',
                    'Jobb technikai SEO',
                    'Több backlink',
                ],
                'improvement_areas' => [
                    'Tartalom bővítése',
                    'Meta leírás optimalizálása',
                    'Gyorsabb betöltési idő',
                ],
                'target_position' => max(1, $position - fake()->numberBetween(5, 15)),
                'estimated_timeframe' => fake()->randomElement(['1-2 hónap', '2-3 hónap', '3-6 hónap']),
                'quick_wins' => [
                    'Title tag optimalizálás',
                    'Belső linkelés javítása',
                ],
            ],
            'ai_analysis' => fake()->paragraphs(3, true),
        ];
    }

    private function generateOrganicResults(): array
    {
        $results = [];
        $count = fake()->numberBetween(8, 10);

        for ($i = 1; $i <= $count; $i++) {
            $results[] = [
                'position' => $i,
                'title' => fake()->sentence(),
                'link' => 'https://' . fake()->domainName() . '/' . fake()->slug(),
                'snippet' => fake()->paragraph(),
                'displayed_link' => fake()->domainName() . ' › ' . fake()->word(),
            ];
        }

        return $results;
    }
}
