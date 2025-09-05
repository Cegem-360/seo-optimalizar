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
        Schema::create('page_speed_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('url');
            $table->enum('strategy', ['mobile', 'desktop']);
            
            // Overall scores
            $table->integer('performance_score')->nullable();
            $table->integer('accessibility_score')->nullable();
            $table->integer('best_practices_score')->nullable();
            $table->integer('seo_score')->nullable();
            
            // Core Web Vitals
            $table->decimal('lcp_value', 8, 2)->nullable(); // Largest Contentful Paint (ms)
            $table->string('lcp_display')->nullable();
            $table->decimal('lcp_score', 3, 2)->nullable();
            
            $table->decimal('fcp_value', 8, 2)->nullable(); // First Contentful Paint (ms)
            $table->string('fcp_display')->nullable();
            $table->decimal('fcp_score', 3, 2)->nullable();
            
            $table->decimal('cls_value', 5, 3)->nullable(); // Cumulative Layout Shift
            $table->string('cls_display')->nullable();
            $table->decimal('cls_score', 3, 2)->nullable();
            
            $table->decimal('speed_index_value', 8, 2)->nullable(); // Speed Index (ms)
            $table->string('speed_index_display')->nullable();
            $table->decimal('speed_index_score', 3, 2)->nullable();
            
            // Additional metadata
            $table->json('raw_data')->nullable(); // Store full API response for detailed analysis
            $table->timestamp('analyzed_at');
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['project_id', 'strategy', 'analyzed_at']);
            $table->index(['project_id', 'analyzed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_speed_results');
    }
};
