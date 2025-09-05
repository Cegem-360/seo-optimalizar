<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Keyword>
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
            'local seo', 'mobile optimization', 'website development'
        ];
        
        return [
            'project_id' => Project::factory(),
            'keyword' => $this->faker->randomElement($keywords),
            'category' => $this->faker->randomElement(['brand', 'product', 'service', 'informational']),
            'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
            'geo_target' => $this->faker->randomElement(['global', 'US', 'UK', 'HU', 'DE']),
            'language' => $this->faker->randomElement(['en', 'hu', 'de']),
            'search_volume' => $this->faker->optional(0.7)->numberBetween(10, 50000),
            'difficulty_score' => $this->faker->optional(0.6)->numberBetween(1, 100),
            'intent_type' => $this->faker->randomElement(['informational', 'navigational', 'transactional', 'commercial']),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    public function transactional(): static
    {
        return $this->state(fn (array $attributes) => [
            'intent_type' => 'transactional',
        ]);
    }

    public function informational(): static
    {
        return $this->state(fn (array $attributes) => [
            'intent_type' => 'informational',
        ]);
    }
}
