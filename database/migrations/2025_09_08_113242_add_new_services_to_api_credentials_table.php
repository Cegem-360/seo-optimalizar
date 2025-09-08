<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_credentials', function (Blueprint $table) {
            // SQLite doesn't support ALTER COLUMN ENUM, so we need to recreate the table
            // First, let's add the new columns temporarily
        });

        // For SQLite, we need to recreate the table with new enum values
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data
            $existingData = DB::table('api_credentials')->get();
            
            // Drop the existing table
            Schema::dropIfExists('api_credentials');
            
            // Recreate the table with updated enum values
            Schema::create('api_credentials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->enum('service', [
                    'google_search_console',
                    'google_analytics',
                    'google_pagespeed_insights',
                    'google_ads',
                    'gemini',
                    'serpapi',
                    'mobile_friendly_test'
                ]);
                $table->text('credentials'); // Encrypted JSON
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'service']);
                $table->index(['project_id', 'is_active']);
            });
            
            // Restore existing data
            foreach ($existingData as $record) {
                DB::table('api_credentials')->insert((array) $record);
            }
        } else {
            // For other databases (MySQL, PostgreSQL), use ALTER TABLE
            DB::statement("ALTER TABLE api_credentials MODIFY COLUMN service ENUM('google_search_console', 'google_analytics', 'google_pagespeed_insights', 'google_ads', 'gemini', 'serpapi', 'mobile_friendly_test')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data
            $existingData = DB::table('api_credentials')->get();
            
            // Drop the existing table
            Schema::dropIfExists('api_credentials');
            
            // Recreate the table with original enum values
            Schema::create('api_credentials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->enum('service', [
                    'google_search_console',
                    'google_analytics',
                    'google_pagespeed_insights',
                    'serpapi',
                    'mobile_friendly_test'
                ]);
                $table->text('credentials'); // Encrypted JSON
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'service']);
                $table->index(['project_id', 'is_active']);
            });
            
            // Restore existing data (excluding new services)
            foreach ($existingData as $record) {
                if (!in_array($record->service, ['google_ads', 'gemini'])) {
                    DB::table('api_credentials')->insert((array) $record);
                }
            }
        } else {
            DB::statement("ALTER TABLE api_credentials MODIFY COLUMN service ENUM('google_search_console', 'google_analytics', 'google_pagespeed_insights', 'serpapi', 'mobile_friendly_test')");
        }
    }
};