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
        Schema::table('competitor_analyses', function (Blueprint $table) {
            $table->boolean('ai_discovered')->default(false)->after('analyzed_at');
            $table->string('competitor_type')->nullable()->after('ai_discovered'); // direct, indirect, content
            $table->integer('strength_score')->nullable()->after('competitor_type'); // 1-10 skála
            $table->text('relevance_reason')->nullable()->after('strength_score');
            $table->json('main_advantages')->nullable()->after('relevance_reason');
            $table->string('estimated_traffic')->nullable()->after('main_advantages'); // low, medium, high
            $table->text('content_focus')->nullable()->after('estimated_traffic');
            $table->json('competitor_strengths')->nullable()->after('content_focus');
            $table->json('competitor_weaknesses')->nullable()->after('competitor_strengths');
            $table->json('project_strengths')->nullable()->after('competitor_weaknesses');
            $table->json('project_weaknesses')->nullable()->after('project_strengths');
            $table->json('opportunities')->nullable()->after('project_weaknesses');
            $table->json('threats')->nullable()->after('opportunities');
            $table->json('action_items')->nullable()->after('threats');
            $table->integer('competitive_advantage_score')->nullable()->after('action_items');
            $table->text('ai_analysis_summary')->nullable()->after('competitive_advantage_score');

            // Indexek hozzáadása
            $table->index('ai_discovered');
            $table->index('competitor_type');
            $table->index('strength_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitor_analyses', function (Blueprint $table) {
            $table->dropColumn([
                'ai_discovered',
                'competitor_type',
                'strength_score',
                'relevance_reason',
                'main_advantages',
                'estimated_traffic',
                'content_focus',
                'competitor_strengths',
                'competitor_weaknesses',
                'project_strengths',
                'project_weaknesses',
                'opportunities',
                'threats',
                'action_items',
                'competitive_advantage_score',
                'ai_analysis_summary',
            ]);
        });
    }
};
