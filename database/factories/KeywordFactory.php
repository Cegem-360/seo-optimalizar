<?php

namespace Database\Factories;

use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keyword>
 */
class KeywordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $keywords = [
            'seo optimization', 'keyword research', 'digital marketing',
            'content marketing', 'web design', 'social media marketing',
            'email marketing', 'ppc advertising', 'conversion rate optimization',
            'local seo', 'mobile optimization', 'website development',
        ];

        return [
            'project_id' => Project::factory(),
            'keyword' => fake()->randomElement($keywords),
            'category' => fake()->randomElement(['brand', 'product', 'service', 'informational']),
            'priority' => fake()->randomElement(['high', 'medium', 'low']),
            'geo_target' => fake()->randomElement(['global', 'US', 'UK', 'HU', 'DE']),
            'language' => fake()->randomElement(['en', 'hu', 'de']),
            'search_volume' => fake()->optional(0.7)->numberBetween(10, 50000),
            'difficulty_score' => fake()->optional(0.6)->numberBetween(1, 100),
            'intent_type' => fake()->randomElement(['informational', 'navigational', 'transactional', 'commercial']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'priority' => 'high',
        ]);
    }

    public function transactional(): static
    {
        return $this->state(fn (array $attributes): array => [
            'intent_type' => 'transactional',
        ]);
    }

    public function informational(): static
    {
        return $this->state(fn (array $attributes): array => [
            'intent_type' => 'informational',
        ]);
    }
}
