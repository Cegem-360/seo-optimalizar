<?php

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
        Schema::create('analysis_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('section_type'); // title, meta, content, images, performance, etc.
            $table->string('section_name'); // Emberi olvasható név
            $table->integer('score')->nullable(); // Szakasz pontszám
            $table->string('status')->nullable(); // good, warning, error
            $table->json('findings')->nullable(); // Megállapítások lista
            $table->json('recommendations')->nullable(); // Javaslatok lista
            $table->json('data')->nullable(); // Strukturált adatok
            $table->text('summary')->nullable(); // Szöveges összefoglaló
            $table->integer('priority')->default(0); // Fontossági sorrend
            $table->timestamps();

            $table->index(['website_analysis_id', 'section_type']);
            $table->index(['website_analysis_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_sections');
    }
};
