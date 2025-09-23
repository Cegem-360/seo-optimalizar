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
        Schema::create('competitor_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Keyword::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();

            // Versenytárs adatok
            $table->string('competitor_domain');
            $table->string('competitor_url')->nullable();
            $table->integer('position');

            // Versenytárs erősségek
            $table->integer('domain_authority')->nullable();
            $table->integer('page_authority')->nullable();
            $table->integer('backlinks_count')->nullable();

            // Tartalom elemzés
            $table->integer('content_length')->nullable();
            $table->integer('keyword_density')->nullable();
            $table->boolean('has_schema_markup')->default(false);
            $table->boolean('has_featured_snippet')->default(false);

            // Technikai SEO
            $table->float('page_speed_score')->nullable();
            $table->boolean('is_mobile_friendly')->default(false);
            $table->boolean('has_ssl')->default(false);

            // Meta adatok
            $table->string('title_tag', 500)->nullable();
            $table->text('meta_description')->nullable();
            $table->json('headers_structure')->nullable();

            // Elemzés dátuma
            $table->timestamp('analyzed_at')->nullable();

            $table->timestamps();

            // Indexek
            $table->index(['keyword_id', 'position']);
            $table->index(['project_id', 'created_at']);
            $table->index('competitor_domain');
            $table->index('analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_analyses');
    }
};
