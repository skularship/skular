<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tes IQ Latihan: the test (duration, material PDF, mentor notes), its
     * question bank, mentee attempts with the frozen question set and the
     * server deadline, one answer row per viewed question (choice, time
     * spent) and the daily AI mentor quota. New tables only.
     *
     * Index names are explicit: MySQL allows at most 64 characters.
     */
    public function up(): void
    {
        Schema::create('iq_tests', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('title');
            $table->text('intro')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(20);
            $table->string('status', 16)->default('draft');
            $table->string('material_pdf_path')->nullable();
            $table->string('material_pdf_name', 120)->nullable();
            $table->timestamp('material_pdf_updated_at')->nullable();
            $table->longText('mentor_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('iq_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iq_test_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('subtest', 24);
            $table->text('passage')->nullable();
            $table->text('prompt');
            $table->string('image_path')->nullable();
            $table->string('image_alt', 500)->nullable();
            $table->string('option_a', 500)->nullable();
            $table->string('option_b', 500)->nullable();
            $table->string('option_c', 500)->nullable();
            $table->string('option_d', 500)->nullable();
            $table->char('answer_key', 1)->nullable();
            $table->text('explanation')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['iq_test_id', 'number'], 'iq_questions_test_number_unique');
        });

        Schema::create('iq_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('iq_test_id')->constrained()->restrictOnDelete();
            $table->string('status', 16)->default('in_progress');
            $table->json('question_ids');
            $table->timestamp('started_at');
            $table->timestamp('deadline_at');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('current_question_id')->nullable()->constrained('iq_questions')->nullOnDelete();
            $table->timestamp('current_viewed_at')->nullable();
            $table->unsignedSmallInteger('answered_count')->nullable();
            $table->unsignedSmallInteger('correct_count')->nullable();
            $table->unsignedSmallInteger('total_questions');
            $table->unsignedTinyInteger('score_percent')->nullable();
            $table->string('category', 64)->nullable();
            $table->json('subtest_scores')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'iq_attempts_user_status_index');
        });

        Schema::create('iq_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iq_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('iq_question_id')->constrained()->restrictOnDelete();
            $table->char('choice', 1)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->unsignedSmallInteger('changes')->default(0);
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamps();

            $table->unique(['iq_attempt_id', 'iq_question_id'], 'iq_answers_attempt_question_unique');
        });

        Schema::create('iq_mentor_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'usage_date'], 'iq_mentor_usages_user_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iq_mentor_usages');
        Schema::dropIfExists('iq_answers');
        Schema::dropIfExists('iq_attempts');
        Schema::dropIfExists('iq_questions');
        Schema::dropIfExists('iq_tests');
    }
};
