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
        Schema::create('notification_preferences', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
            $blueprint->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Email notification preferences
            $blueprint->boolean('email_ranking_changes')->default(true);
            $blueprint->boolean('email_top3_achievements')->default(true);
            $blueprint->boolean('email_first_page_entries')->default(true);
            $blueprint->boolean('email_significant_drops')->default(true);
            $blueprint->boolean('email_weekly_summary')->default(true);

            // In-app notification preferences
            $blueprint->boolean('app_ranking_changes')->default(true);
            $blueprint->boolean('app_top3_achievements')->default(true);
            $blueprint->boolean('app_first_page_entries')->default(true);
            $blueprint->boolean('app_significant_drops')->default(true);

            // Thresholds
            $blueprint->integer('significant_change_threshold')->default(5); // positions
            $blueprint->boolean('only_significant_changes')->default(false);

            $blueprint->timestamps();

            $blueprint->unique(['user_id', 'project_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
