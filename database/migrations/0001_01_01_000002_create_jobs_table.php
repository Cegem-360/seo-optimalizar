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
        $this->builder->create('jobs', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('queue')->index();
            $blueprint->longText('payload');
            $blueprint->unsignedTinyInteger('attempts');
            $blueprint->unsignedInteger('reserved_at')->nullable();
            $blueprint->unsignedInteger('available_at');
            $blueprint->unsignedInteger('created_at');
        });

        $this->builder->create('job_batches', function (Blueprint $blueprint): void {
            $blueprint->string('id')->primary();
            $blueprint->string('name');
            $blueprint->integer('total_jobs');
            $blueprint->integer('pending_jobs');
            $blueprint->integer('failed_jobs');
            $blueprint->longText('failed_job_ids');
            $blueprint->mediumText('options')->nullable();
            $blueprint->integer('cancelled_at')->nullable();
            $blueprint->integer('created_at');
            $blueprint->integer('finished_at')->nullable();
        });

        $this->builder->create('failed_jobs', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('uuid')->unique();
            $blueprint->text('connection');
            $blueprint->text('queue');
            $blueprint->longText('payload');
            $blueprint->longText('exception');
            $blueprint->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->builder->dropIfExists('jobs');
        $this->builder->dropIfExists('job_batches');
        $this->builder->dropIfExists('failed_jobs');
    }
};
