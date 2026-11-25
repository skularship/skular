<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One question of the bank. The answer key, accepted answers, outline and
     * explanation are hidden on the model and never sent to a Gratis browser.
     * `code` never changes once the question has answers. New table only.
     */
    public function up(): void
    {
        Schema::create('tryout_questions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('tryout_unit_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('exam_type', 16)->default('ielts');
            $table->string('skill', 24);
            $table->unsignedSmallInteger('number')->nullable();
            $table->unsignedTinyInteger('part')->nullable();
            $table->unsignedTinyInteger('level');
            $table->string('question_type', 24);
            $table->string('source_type', 40)->nullable();
            $table->text('prompt');
            $table->json('prompt_items')->nullable();
            $table->json('prompt_table')->nullable();
            $table->string('prompt_image_path')->nullable();
            $table->json('options')->nullable();
            $table->string('answer_key')->nullable();
            $table->json('accepted_answers')->nullable();
            $table->unsignedTinyInteger('word_limit')->nullable();
            $table->unsignedSmallInteger('min_words')->nullable();
            $table->unsignedSmallInteger('prep_seconds')->nullable();
            $table->unsignedSmallInteger('speak_seconds')->nullable();
            $table->unsignedTinyInteger('max_score')->default(1);
            $table->string('rubric_key')->nullable();
            $table->json('answer_outline')->nullable();
            $table->text('explanation')->nullable();
            $table->json('theory_tags')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamps();

            $table->index(['exam_type', 'skill', 'level', 'status']);
            $table->index(['tryout_unit_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryout_questions');
    }
};
