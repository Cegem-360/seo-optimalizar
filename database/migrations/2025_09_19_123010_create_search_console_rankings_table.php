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
        Schema::create('search_console_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Keyword::class)->nullable()->constrained()->nullOnDelete();

            // Search Console specifikus adatok
            $table->string('query');
            $table->string('page');
            $table->string('country', 10)->default('hun');
            $table->string('device')->default('desktop');

            // Pozíció adatok
            $table->decimal('position', 5, 2);
            $table->decimal('previous_position', 5, 2)->nullable();
            $table->integer('position_change')->nullable();

            // Metrikák
            $table->integer('clicks')->default(0);
            $table->integer('impressions')->default(0);
            $table->decimal('ctr', 5, 4)->default(0);

            // Időszak adatok
            $table->date('date_from');
            $table->date('date_to');
            $table->integer('days_count')->default(1);

            // Összehasonlítási adatok
            $table->integer('previous_clicks')->nullable();
            $table->integer('previous_impressions')->nullable();
            $table->decimal('previous_ctr', 5, 4)->nullable();
            $table->decimal('clicks_change_percent', 5, 2)->nullable();
            $table->decimal('impressions_change_percent', 5, 2)->nullable();

            // Meta adatok
            $table->json('raw_data')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            // Indexek
            $table->index(['project_id', 'date_from', 'date_to']);
            $table->index(['project_id', 'query']);
            $table->index(['project_id', 'position']);
            $table->index(['fetched_at']);
            $table->index(['date_from', 'date_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_console_rankings');
    }
};
