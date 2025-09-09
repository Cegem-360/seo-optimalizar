<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = fake()->domainName();

        return [
            'name' => fake()->company() . ' SEO Project',
            'url' => 'https://' . $domain,
            'description' => fake()->optional()->paragraph(),
        ];
    }

    public function withUrl(string $url): static
    {
        return $this->state(fn (array $attributes): array => [
            'url' => $url,
        ]);
    }
}
