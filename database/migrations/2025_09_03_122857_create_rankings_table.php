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
        $this->builder->create('rankings', function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $blueprint->integer('position')->nullable();
            $blueprint->integer('previous_position')->nullable();
            $blueprint->string('url')->nullable();
            $blueprint->boolean('featured_snippet')->default(false);
            $blueprint->json('serp_features')->nullable();
            $blueprint->timestamp('checked_at');
            $blueprint->timestamps();

            $blueprint->index(['keyword_id', 'checked_at']);
            $blueprint->index('position');
            $blueprint->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->builder->dropIfExists('rankings');
    }
};
