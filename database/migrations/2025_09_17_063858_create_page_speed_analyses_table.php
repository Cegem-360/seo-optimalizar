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
        Schema::create('page_speed_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('keyword_id')->nullable()->constrained()->onDelete('cascade');

            // Tesztelt URL
            $table->string('tested_url');
            $table->enum('device_type', ['mobile', 'desktop'])->default('desktop');

            // Core Web Vitals
            $table->float('lcp')->nullable()->comment('Largest Contentful Paint (seconds)');
            $table->float('fid')->nullable()->comment('First Input Delay (milliseconds)');
            $table->float('cls')->nullable()->comment('Cumulative Layout Shift');
            $table->float('fcp')->nullable()->comment('First Contentful Paint (seconds)');
            $table->float('inp')->nullable()->comment('Interaction to Next Paint (milliseconds)');
            $table->float('ttfb')->nullable()->comment('Time to First Byte (milliseconds)');

            // Performance Score
            $table->integer('performance_score')->nullable();
            $table->integer('accessibility_score')->nullable();
            $table->integer('best_practices_score')->nullable();
            $table->integer('seo_score')->nullable();

            // Oldal méret és erőforrások
            $table->integer('total_page_size')->nullable()->comment('Total size in bytes');
            $table->integer('total_requests')->nullable();
            $table->float('load_time')->nullable()->comment('Total load time in seconds');

            // Erőforrás részletek (JSON)
            $table->json('resource_breakdown')->nullable()->comment('Breakdown by type: images, scripts, css, etc.');
            $table->json('third_party_resources')->nullable();

            // Javaslatok és problémák (JSON)
            $table->json('opportunities')->nullable()->comment('Performance improvement opportunities');
            $table->json('diagnostics')->nullable()->comment('Issues and warnings');

            // Képek és média
            $table->integer('images_count')->nullable();
            $table->integer('unoptimized_images')->nullable();
            $table->integer('images_without_alt')->nullable();

            // JavaScript és CSS
            $table->integer('render_blocking_resources')->nullable();
            $table->integer('unused_css_bytes')->nullable();
            $table->integer('unused_js_bytes')->nullable();

            // Elemzés forrása
            $table->string('analysis_source')->default('pagespeed');
            $table->timestamp('analyzed_at');

            // Teljes API válasz
            $table->json('raw_response')->nullable();

            $table->timestamps();

            // Indexek
            $table->index(['project_id', 'analyzed_at']);
            $table->index(['keyword_id', 'analyzed_at']);
            $table->index('tested_url');
            $table->index('device_type');
            $table->index('performance_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_speed_analyses');
    }
};
