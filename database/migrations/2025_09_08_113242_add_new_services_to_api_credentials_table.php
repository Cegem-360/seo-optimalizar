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
        $this->builder->table('api_credentials', function (Blueprint $blueprint): void {
            // SQLite doesn't support ALTER COLUMN ENUM, so we need to recreate the table
            // First, let's add the new columns temporarily
        });

        // For SQLite, we need to recreate the table with new enum values
        if ($this->builder->getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data
            $existingData = $this->databaseManager->table('api_credentials')->get();

            // Drop the existing table
            $this->builder->dropIfExists('api_credentials');

            // Recreate the table with updated enum values
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
        } else {
            // For other databases (MySQL, PostgreSQL), use ALTER TABLE
            $this->databaseManager->statement("ALTER TABLE api_credentials MODIFY COLUMN service ENUM('google_search_console', 'google_analytics', 'google_pagespeed_insights', 'google_ads', 'gemini', 'serpapi', 'mobile_friendly_test')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->builder->getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data
            $existingData = $this->databaseManager->table('api_credentials')->get();

            // Drop the existing table
            $this->builder->dropIfExists('api_credentials');

            // Recreate the table with original enum values
            $this->builder->create('api_credentials', function (Blueprint $blueprint): void {
                $blueprint->id();
                $blueprint->foreignId('project_id')->constrained()->cascadeOnDelete();
                $blueprint->enum('service', [
                    'google_search_console',
                    'google_analytics',
                    'google_pagespeed_insights',
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

            // Restore existing data (excluding new services)
            foreach ($existingData as $record) {
                if (! in_array($record->service, ['google_ads', 'gemini'])) {
                    $this->databaseManager->table('api_credentials')->insert((array) $record);
                }
            }
        } else {
            $this->databaseManager->statement("ALTER TABLE api_credentials MODIFY COLUMN service ENUM('google_search_console', 'google_analytics', 'google_pagespeed_insights', 'serpapi', 'mobile_friendly_test')");
        }
    }
};
