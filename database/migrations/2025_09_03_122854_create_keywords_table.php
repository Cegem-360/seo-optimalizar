<?php

declare(strict_types=1);

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
        Schema::create('keywords', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $blueprint->string('keyword');
            $blueprint->string('category')->nullable();
            $blueprint->string('priority')->default('medium');
            $blueprint->string('geo_target')->default('global');
            $blueprint->string('language')->default('en');
            $blueprint->integer('search_volume')->nullable();
            $blueprint->integer('difficulty_score')->nullable();
            $blueprint->string('intent_type')->nullable();
            $blueprint->text('notes')->nullable();
            $blueprint->integer('competition_index')->nullable();
            $blueprint->decimal('low_top_of_page_bid', 10, 2)->nullable();
            $blueprint->decimal('high_top_of_page_bid', 10, 2)->nullable();
            $blueprint->json('monthly_search_volumes')->nullable();
            $blueprint->timestamp('historical_metrics_updated_at')->nullable();
            $blueprint->timestamps();

            $blueprint->index(['project_id', 'keyword']);
            $blueprint->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};
