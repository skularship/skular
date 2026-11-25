<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Shared content bank of the Try Out: a Reading passage, a Listening audio
     * or a Writing/Speaking prompt group. One unit can be reused by several
     * Try Outs through tryout_section_unit. New table only.
     */
    public function up(): void
    {
        Schema::create('tryout_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('exam_type', 16)->default('ielts');
            $table->string('skill', 24);
            $table->string('title');
            $table->string('context')->nullable();
            $table->longText('passage')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('audio_path')->nullable();
            $table->unsignedSmallInteger('audio_duration_seconds')->nullable();
            $table->string('image_path')->nullable();
            $table->json('image_points')->nullable();
            $table->json('attachment')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamps();

            $table->index(['exam_type', 'skill', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryout_units');
    }
};
