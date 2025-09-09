<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class() extends Migration
{
    public function __construct(private readonly \Illuminate\Database\Schema\Builder $builder) {}

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->builder->create('page_speed_results', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('project_id')->constrained()->cascadeOnDelete();
            $blueprint->string('url');
            $blueprint->enum('strategy', ['mobile', 'desktop']);

            // Overall scores
            $blueprint->integer('performance_score')->nullable();
            $blueprint->integer('accessibility_score')->nullable();
            $blueprint->integer('best_practices_score')->nullable();
            $blueprint->integer('seo_score')->nullable();

            // Core Web Vitals
            $blueprint->decimal('lcp_value', 8, 2)->nullable(); // Largest Contentful Paint (ms)
            $blueprint->string('lcp_display')->nullable();
            $blueprint->decimal('lcp_score', 3, 2)->nullable();

            $blueprint->decimal('fcp_value', 8, 2)->nullable(); // First Contentful Paint (ms)
            $blueprint->string('fcp_display')->nullable();
            $blueprint->decimal('fcp_score', 3, 2)->nullable();

            $blueprint->decimal('cls_value', 5, 3)->nullable(); // Cumulative Layout Shift
            $blueprint->string('cls_display')->nullable();
            $blueprint->decimal('cls_score', 3, 2)->nullable();

            $blueprint->decimal('speed_index_value', 8, 2)->nullable(); // Speed Index (ms)
            $blueprint->string('speed_index_display')->nullable();
            $blueprint->decimal('speed_index_score', 3, 2)->nullable();

            // Additional metadata
            $blueprint->json('raw_data')->nullable(); // Store full API response for detailed analysis
            $blueprint->timestamp('analyzed_at');
            $blueprint->timestamps();

            // Indexes for efficient querying
            $blueprint->index(['project_id', 'strategy', 'analyzed_at']);
            $blueprint->index(['project_id', 'analyzed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->builder->dropIfExists('page_speed_results');
    }
};
