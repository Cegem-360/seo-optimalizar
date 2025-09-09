<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class() extends Migration
{
    public function __construct(private readonly \Illuminate\Database\Schema\Builder $builder, private readonly \Illuminate\Database\DatabaseManager $databaseManager) {}

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove any existing SerpAPI credentials
        $this->databaseManager->table('api_credentials')->where('service', 'serpapi')->delete();

        // For SQLite, recreate the table without SerpAPI
        if ($this->builder->getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data (excluding serpapi)
            $existingData = $this->databaseManager->table('api_credentials')->where('service', '!=', 'serpapi')->get();

            // Drop the existing table
            $this->builder->dropIfExists('api_credentials');

            // Recreate the table without SerpAPI
            $this->builder->create('api_credentials', function (Blueprint $blueprint): void {
                $blueprint->id();
                $blueprint->foreignId('project_id')->constrained()->cascadeOnDelete();
                $blueprint->enum('service', [
                    'google_search_console',
                    'google_analytics',
                    'google_pagespeed_insights',
                    'google_ads',
                    'gemini',
                    'mobile_friendly_test',
                ]);
                $blueprint->text('credentials'); // Encrypted JSON
                $blueprint->boolean('is_active')->default(true);
                $blueprint->timestamp('last_used_at')->nullable();
                $blueprint->timestamps();

                $blueprint->unique(['project_id', 'service']);
                $blueprint->index(['project_id', 'is_active']);
            });

            // Restore existing data
            foreach ($existingData as $record) {
                $this->databaseManager->table('api_credentials')->insert((array) $record);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite, recreate the table with SerpAPI
        if ($this->builder->getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data
            $existingData = $this->databaseManager->table('api_credentials')->get();

            // Drop the existing table
            $this->builder->dropIfExists('api_credentials');

            // Recreate the table with SerpAPI
            $this->builder->create('api_credentials', function (Blueprint $blueprint): void {
                $blueprint->id();
                $blueprint->foreignId('project_id')->constrained()->cascadeOnDelete();
                $blueprint->enum('service', [
                    'google_search_console',
                    'google_analytics',
                    'google_pagespeed_insights',
                    'google_ads',
                    'gemini',
                    'serpapi',
                    'mobile_friendly_test',
                ]);
                $blueprint->text('credentials'); // Encrypted JSON
                $blueprint->boolean('is_active')->default(true);
                $blueprint->timestamp('last_used_at')->nullable();
                $blueprint->timestamps();

                $blueprint->unique(['project_id', 'service']);
                $blueprint->index(['project_id', 'is_active']);
            });

            // Restore existing data
            foreach ($existingData as $record) {
                $this->databaseManager->table('api_credentials')->insert((array) $record);
            }
        }
    }
};
