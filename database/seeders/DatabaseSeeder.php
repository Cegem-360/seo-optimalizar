<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function __construct(private readonly Hasher $hasher) {}

    public function run(): void
    {
        // Create admin user
        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => $this->hasher->make('password'),
            'email_verified_at' => now(),
        ]);

        // Create SEO projects
        $project1 = Project::query()->create([
            'name' => 'Main Website SEO',
            'url' => 'https://example.com',
            'description' => 'SEO monitoring for the main company website',
        ]);

        $project2 = Project::query()->create([
            'name' => 'Blog SEO Tracking',
            'url' => 'https://blog.example.com',
            'description' => 'SEO monitoring for the company blog',
        ]);

        // Attach admin to projects
        $user->projects()->attach([$project1->id, $project2->id]);
    }
}
