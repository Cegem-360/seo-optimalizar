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
        $this->builder->create('cache', function (Blueprint $blueprint): void {
            $blueprint->string('key')->primary();
            $blueprint->mediumText('value');
            $blueprint->integer('expiration');
        });

        $this->builder->create('cache_locks', function (Blueprint $blueprint): void {
            $blueprint->string('key')->primary();
            $blueprint->string('owner');
            $blueprint->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->builder->dropIfExists('cache');
        $this->builder->dropIfExists('cache_locks');
    }
};
