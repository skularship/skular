<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Settings of one subtest (Listening, Reading, Writing, Speaking) inside a
     * Try Out, plus the pivot that picks which units form its question pool.
     * The order of the subtests comes from `ordering`, never from code.
     */
    public function up(): void
    {
        Schema::create('tryout_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tryout_id')->constrained()->cascadeOnDelete();
            $table->string('skill', 24);
            $table->unsignedTinyInteger('ordering')->default(0);
            $table->unsignedInteger('duration_seconds');
            $table->unsignedInteger('transfer_seconds')->default(0);
            $table->unsignedSmallInteger('question_count');
            $table->boolean('is_adaptive')->default(false);
            $table->unsignedTinyInteger('start_level')->default(3);
            $table->unsignedTinyInteger('questions_per_unit')->nullable();
            $table->unsignedTinyInteger('audio_plays_practice')->default(2);
            $table->unsignedTinyInteger('audio_plays_exam')->default(1);
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->unique(['tryout_id', 'skill']);
        });

        Schema::create('tryout_section_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tryout_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tryout_unit_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('ordering')->default(0);
            $table->timestamps();

            $table->unique(['tryout_section_id', 'tryout_unit_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryout_section_unit');
        Schema::dropIfExists('tryout_sections');
    }
};
