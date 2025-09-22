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
        Schema::create('users', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('email')->unique();
            $blueprint->timestamp('email_verified_at')->nullable();
            $blueprint->string('password');
            $blueprint->rememberToken();
            $blueprint->foreignIdFor(Project::class, 'latest_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $blueprint->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $blueprint): void {
            $blueprint->string('email')->primary();
            $blueprint->string('token');
            $blueprint->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $blueprint): void {
            $blueprint->string('id')->primary();
            $blueprint->foreignIdFor(User::class)->nullable()->index();
            $blueprint->string('ip_address', 45)->nullable();
            $blueprint->text('user_agent')->nullable();
            $blueprint->longText('payload');
            $blueprint->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
