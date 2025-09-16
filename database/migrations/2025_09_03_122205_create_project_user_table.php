<?php

use App\Models\Project;
use App\Models\User;
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
        Schema::create('project_user', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $blueprint->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $blueprint->timestamps();

            $blueprint->unique(['project_id', 'user_id']);
        });

        // Add foreign key constraint for latest_project_id after projects table exists
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->foreign('latest_project_id')->references('id')->on('projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key constraint first
        Schema::table('users', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['latest_project_id']);
        });

        Schema::dropIfExists('project_user');
    }
};
