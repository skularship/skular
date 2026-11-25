<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per mentee per Try Out attempt counting the questions sent to
     * the Try Out mentor (same pattern as module_mentor_usages). New table only.
     */
    public function up(): void
    {
        Schema::create('tryout_mentor_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tryout_attempt_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'tryout_attempt_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryout_mentor_usages');
    }
};
