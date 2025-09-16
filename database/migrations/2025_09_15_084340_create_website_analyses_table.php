<?php

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
        Schema::create('website_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->string('analysis_type'); // seo, ux, content, technical, competitor
            $table->string('ai_provider'); // openai, claude, gemini, etc.
            $table->string('ai_model')->nullable(); // gpt-4, claude-3, etc.
            $table->json('request_params')->nullable(); // prompt, settings, etc.
            $table->text('raw_response')->nullable(); // teljes AI válasz
            $table->integer('overall_score')->nullable(); // összpontszám ha van
            $table->json('scores')->nullable(); // különböző pontszámok
            $table->json('metadata')->nullable(); // egyéb metaadatok
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'analysis_type']);
            $table->index(['project_id', 'status']);
            $table->index('analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_analyses');
    }
};
