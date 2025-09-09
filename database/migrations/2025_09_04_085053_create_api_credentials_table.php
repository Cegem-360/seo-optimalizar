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
        $this->builder->create('api_credentials', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('project_id')->constrained()->cascadeOnDelete();
            $blueprint->enum('service', [
                'google_search_console',
                'google_analytics',
                'google_pagespeed_insights',
                'serpapi',
                'mobile_friendly_test',
            ]);
            $blueprint->text('credentials'); // Encrypted JSON
            $blueprint->boolean('is_active')->default(true);
            $blueprint->timestamp('last_used_at')->nullable();
            $blueprint->timestamps();

            $blueprint->unique(['project_id', 'service']);
            $blueprint->index(['project_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->builder->dropIfExists('api_credentials');
    }
};
