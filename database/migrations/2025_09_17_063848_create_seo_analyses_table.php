<?php

declare(strict_types=1);

use App\Models\Keyword;
use App\Models\Project;
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
        Schema::create('seo_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Keyword::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();

            // Versenyhelyzet és szándék
            $table->enum('competition_level', ['low', 'medium', 'high'])->nullable();
            $table->enum('search_intent', ['informational', 'commercial', 'transactional', 'navigational'])->nullable();

            // Tartalom típusok (JSON tömb)
            $table->json('dominant_content_types')->nullable();

            // Lehetőségek és kihívások (JSON tömbök)
            $table->json('opportunities')->nullable();
            $table->json('challenges')->nullable();
            $table->json('optimization_tips')->nullable();

            // Összefoglaló
            $table->text('summary')->nullable();

            // Pozíció elemzés
            $table->enum('position_rating', ['kiváló', 'jó', 'közepes', 'gyenge', 'kritikus'])->nullable();
            $table->integer('current_position')->nullable();
            $table->integer('target_position')->nullable();
            $table->string('estimated_timeframe')->nullable();

            // Versenytárs elemzés (JSON tömbök)
            $table->json('main_competitors')->nullable();
            $table->json('competitor_advantages')->nullable();
            $table->json('improvement_areas')->nullable();
            $table->json('quick_wins')->nullable();

            // Részletes elemzés
            $table->text('detailed_analysis')->nullable();

            // Teljes nyers válasz tárolása
            $table->json('raw_response')->nullable();

            // Elemzés forrása
            $table->string('analysis_source')->default('gemini');

            $table->timestamps();

            // Indexek
            $table->index(['keyword_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
            $table->index('competition_level');
            $table->index('search_intent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_analyses');
    }
};
