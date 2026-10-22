<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_practice_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_form_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->json('ai_feedback')->nullable();
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->string('ai_status')->default('pending');
            $table->text('ai_error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'practice_form_id']);
            $table->index(['user_id', 'module_id']);
            $table->index('ai_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_practice_responses');
    }
};
