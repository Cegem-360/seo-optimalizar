<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table with new enum values
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // Backup existing data
            $existingData = DB::table('api_credentials')->get();

            // Drop the existing table
            Schema::dropIfExists('api_credentials');

            // Recreate the table with updated enum values including google_analytics_4
            Schema::create('api_credentials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->enum('service', [
                    'google_search_console',
                    'google_analytics',
                    'google_analytics_4',  // Added GA4
                    'google_pagespeed_insights',
                    'google_ads',
                    'gemini',
                    'mobile_friendly_test',
                ]);
                $table->text('credentials'); // Encrypted JSON
                $table->string('service_account_file')->nullable(); // Added from previous migration
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
            DB::statement("ALTER TABLE api_credentials MODIFY COLUMN service ENUM('google_search_console', 'google_analytics', 'google_analytics_4', 'google_pagespeed_insights', 'google_ads', 'gemini', 'mobile_friendly_test')");
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

            // Recreate the table without google_analytics_4
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
                $table->string('service_account_file')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'service']);
                $table->index(['project_id', 'is_active']);
            });

            // Restore existing data (excluding GA4)
            foreach ($existingData as $record) {
                if ($record->service !== 'google_analytics_4') {
                    DB::table('api_credentials')->insert((array) $record);
                }
            }
        } else {
            DB::statement("ALTER TABLE api_credentials MODIFY COLUMN service ENUM('google_search_console', 'google_analytics', 'google_pagespeed_insights', 'google_ads', 'gemini', 'mobile_friendly_test')");
        }
    }
};
