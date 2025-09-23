<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Competitor;
use App\Models\CompetitorRanking;
use App\Models\Keyword;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitorRanking>
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
        $position = fake()->numberBetween(1, 100);
        $previousPosition = fake()->optional(0.7)->numberBetween(1, 100);

        return [
            'competitor_id' => Competitor::factory(),
            'keyword_id' => Keyword::factory(),
            'position' => $position,
            'previous_position' => $previousPosition,
            'url' => fake()->optional(0.8)->url(),
            'featured_snippet' => fake()->boolean(5),
            'serp_features' => fake()->optional()->randomElements([
                'featured_snippet', 'people_also_ask', 'image_pack',
                'video_carousel', 'local_pack', 'shopping_results',
            ], fake()->numberBetween(0, 3)),
            'checked_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function topTen(): static
    {
        return $this->state(fn (array $attributes): array => [
            'position' => fake()->numberBetween(1, 10),
        ]);
    }

    public function topThree(): static
    {
        return $this->state(fn (array $attributes): array => [
            'position' => fake()->numberBetween(1, 3),
        ]);
    }

    public function withFeaturedSnippet(): static
    {
        return $this->state(fn (array $attributes): array => [
            'featured_snippet' => true,
            'position' => 1,
        ]);
    }
}
