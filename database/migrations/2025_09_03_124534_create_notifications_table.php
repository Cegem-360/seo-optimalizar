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
        $this->builder->create('notifications', function (Blueprint $blueprint): void {
            $blueprint->uuid('id')->primary();
            $blueprint->string('type');
            $blueprint->morphs('notifiable');
            $blueprint->text('data');
            $blueprint->timestamp('read_at')->nullable();
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->builder->dropIfExists('notifications');
    }
};
