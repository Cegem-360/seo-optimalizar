<?php

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
        Schema::create('serp_analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Keyword::class)->constrained()->cascadeOnDelete();
            $table->string('search_id')->nullable();
            $table->json('organic_results');
            $table->json('serp_metrics');
            $table->json('analysis_data');
            $table->text('ai_analysis')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'keyword_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serp_analysis_results');
    }
};
