<?php

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
        Schema::create('reports', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $blueprint->string('title');
            $blueprint->string('type');
            $blueprint->date('period_start');
            $blueprint->date('period_end');
            $blueprint->json('data')->nullable();
            $blueprint->string('file_path')->nullable();
            $blueprint->timestamp('generated_at')->nullable();
            $blueprint->string('status')->default('pending');
            $blueprint->timestamps();

            $blueprint->index(['project_id', 'type']);
            $blueprint->index('generated_at');
            $blueprint->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
