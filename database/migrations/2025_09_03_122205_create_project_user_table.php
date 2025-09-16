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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
    }
};
