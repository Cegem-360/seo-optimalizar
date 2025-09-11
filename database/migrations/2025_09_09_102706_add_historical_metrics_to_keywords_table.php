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
        Schema::table('keywords', function (Blueprint $table) {
            $table->integer('competition_index')->nullable()->after('difficulty_score');
            $table->decimal('low_top_of_page_bid', 10, 2)->nullable()->after('competition_index');
            $table->decimal('high_top_of_page_bid', 10, 2)->nullable()->after('low_top_of_page_bid');
            $table->json('monthly_search_volumes')->nullable()->after('high_top_of_page_bid');
            $table->timestamp('historical_metrics_updated_at')->nullable()->after('monthly_search_volumes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keywords', function (Blueprint $table) {
            $table->dropColumn([
                'competition_index',
                'low_top_of_page_bid',
                'high_top_of_page_bid',
                'monthly_search_volumes',
                'historical_metrics_updated_at',
            ]);
        });
    }
};
