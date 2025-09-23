<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('page_speed_results', function (Blueprint $table): void {
            // Composite index for project queries
            $table->index(['project_id', 'analyzed_at'], 'idx_page_speed_project_analyzed');

            // Individual index for analyzed_at ordering
            $table->index(['analyzed_at'], 'idx_page_speed_analyzed_at');
        });
    }

    public function down(): void
    {
        Schema::table('page_speed_results', function (Blueprint $table): void {
            $table->dropIndex('idx_page_speed_project_analyzed');
            $table->dropIndex('idx_page_speed_analyzed_at');
        });
    }
};
