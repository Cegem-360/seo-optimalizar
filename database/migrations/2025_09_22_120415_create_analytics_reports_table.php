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
        Schema::create('analytics_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->date('report_date');

            // Overview data
            $table->integer('sessions')->default(0);
            $table->integer('active_users')->default(0);
            $table->integer('total_users')->default(0);
            $table->integer('new_users')->default(0);
            $table->decimal('bounce_rate', 5, 2)->default(0);
            $table->decimal('average_session_duration', 8, 2)->default(0);
            $table->integer('screen_page_views')->default(0);
            $table->integer('conversions')->default(0);

            // JSON data for detailed analytics
            $table->json('traffic_sources')->nullable();
            $table->json('top_pages')->nullable();
            $table->json('user_demographics')->nullable();
            $table->json('device_data')->nullable();
            $table->json('conversion_data')->nullable();
            $table->json('real_time')->nullable();

            // Raw data storage
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['project_id', 'report_date']);
            $table->index(['project_id', 'report_date']);
            $table->index('report_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_reports');
    }
};
