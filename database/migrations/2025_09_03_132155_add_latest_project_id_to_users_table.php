<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class() extends Migration
{
    public function __construct(private readonly \Illuminate\Database\Schema\Builder $builder) {}

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->builder->table('users', function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('latest_project_id')->nullable()->after('remember_token');
            $blueprint->foreign('latest_project_id')->references('id')->on('projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->builder->table('users', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['latest_project_id']);
            $blueprint->dropColumn('latest_project_id');
        });
    }
};
