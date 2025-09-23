<?php

declare(strict_types=1);

use App\Models\Competitor;
use App\Models\Keyword;
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
        Schema::create('competitor_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Competitor::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Keyword::class)->constrained()->cascadeOnDelete();
            $table->integer('position')->nullable();
            $table->integer('previous_position')->nullable();
            $table->string('url')->nullable();
            $table->boolean('featured_snippet')->default(false);
            $table->json('serp_features')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['competitor_id', 'checked_at']);
            $table->index(['keyword_id', 'checked_at']);
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitor_rankings');
    }
};
