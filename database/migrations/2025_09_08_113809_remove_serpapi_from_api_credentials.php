<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove any existing SerpAPI credentials
        DB::table('api_credentials')->where('service', 'serpapi')->delete();

        // For SQLite, recreate the table without SerpAPI
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data (excluding serpapi)
            $existingData = DB::table('api_credentials')->where('service', '!=', 'serpapi')->get();

            // Drop the existing table
            Schema::dropIfExists('api_credentials');

            // Recreate the table without SerpAPI
            Schema::create('api_credentials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->enum('service', [
                    'google_search_console',
                    'google_analytics',
                    'google_pagespeed_insights',
                    'google_ads',
                    'gemini',
                    'mobile_friendly_test',
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // For SQLite, recreate the table with SerpAPI
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data
            $existingData = DB::table('api_credentials')->get();

            // Drop the existing table
            Schema::dropIfExists('api_credentials');

            // Recreate the table with SerpAPI
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
                    'mobile_friendly_test',
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
        }
    }
};
