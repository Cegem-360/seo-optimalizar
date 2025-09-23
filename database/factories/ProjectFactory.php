<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
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
