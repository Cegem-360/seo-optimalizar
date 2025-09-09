<?php

namespace Database\Factories;

use App\Models\ApiCredential;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiCredential>
 */
class ApiCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = fake()->randomElement([
            'google_search_console',
            'google_analytics',
            'google_pagespeed_insights',
            'serpapi',
            'mobile_friendly_test',
        ]);

        $credentials = match ($service) {
            'google_search_console' => [
                'client_id' => fake()->uuid(),
                'client_secret' => fake()->password(20),
                'refresh_token' => fake()->password(30),
            ],
            'google_analytics' => [
                'property_id' => 'GA4-' . fake()->numerify('########'),
                'client_id' => fake()->uuid(),
                'client_secret' => fake()->password(20),
            ],
            'serpapi' => [
                'api_key' => fake()->password(32),
            ],
            default => [
                'api_key' => fake()->password(32),
            ]
        };

        return [
            'project_id' => Project::factory(),
            'service' => $service,
            'credentials' => $credentials,
            'is_active' => fake()->boolean(85),
            'last_used_at' => fake()->optional(0.6)->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function googleSearchConsole(): static
    {
        return $this->state(fn (array $attributes): array => [
            'service' => 'google_search_console',
            'credentials' => [
                'client_id' => fake()->uuid(),
                'client_secret' => fake()->password(20),
                'refresh_token' => fake()->password(30),
            ],
        ]);
    }

    public function serpApi(): static
    {
        return $this->state(fn (array $attributes): array => [
            'service' => 'serpapi',
            'credentials' => [
                'api_key' => fake()->password(32),
            ],
        ]);
    }
}
