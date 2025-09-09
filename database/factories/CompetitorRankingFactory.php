<?php

namespace Database\Factories;

use App\Models\Competitor;
use App\Models\Keyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompetitorRanking>
 */
class CompetitorRankingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $position = $this->faker->numberBetween(1, 100);
        $previousPosition = $this->faker->optional(0.7)->numberBetween(1, 100);
        
        return [
            'competitor_id' => Competitor::factory(),
            'keyword_id' => Keyword::factory(),
            'position' => $position,
            'previous_position' => $previousPosition,
            'url' => $this->faker->optional(0.8)->url(),
            'featured_snippet' => $this->faker->boolean(5),
            'serp_features' => $this->faker->optional()->randomElements([
                'featured_snippet', 'people_also_ask', 'image_pack', 
                'video_carousel', 'local_pack', 'shopping_results'
            ], $this->faker->numberBetween(0, 3)),
            'checked_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function topTen(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $this->faker->numberBetween(1, 10),
        ]);
    }

    public function topThree(): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $this->faker->numberBetween(1, 3),
        ]);
    }

    public function withFeaturedSnippet(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_snippet' => true,
            'position' => 1,
        ]);
    }
}
