<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per mentee and practice form holds the latest answers and
     * outline, mirroring the one-row-per-form pattern already used by
     * user_practice_responses.
     */
    public function up(): void
    {
        Schema::create('user_brainstorming_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_form_id')->constrained()->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->json('outline')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'practice_form_id']);
            $table->index(['user_id', 'module_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_brainstorming_sessions');
    }
};
