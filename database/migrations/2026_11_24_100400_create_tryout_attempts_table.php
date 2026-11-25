<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mentee data of the Try Out, kept apart from the question bank:
     * attempts, one row per subtest of an attempt, and one row per served
     * question. A question row is written before it is shown (response null),
     * which is what lets a refresh resume on the same question.
     *
     * Index names are explicit: MySQL allows at most 64 characters and the
     * generated name of the (section, question) unique index is longer.
     */
    public function up(): void
    {
        $this->dropLeftoversOfFailedRun();

        Schema::create('tryout_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tryout_id')->constrained()->restrictOnDelete();
            $table->string('mode', 16);
            $table->string('status', 16)->default('in_progress');
            $table->string('current_skill', 24)->nullable();
            $table->unsignedTinyInteger('start_level')->default(3);
            $table->decimal('overall_band', 3, 1)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tryout_id', 'status']);
        });

        Schema::create('tryout_attempt_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tryout_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tryout_section_id')->constrained()->restrictOnDelete();
            $table->string('skill', 24);
            $table->string('status', 16)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedTinyInteger('current_level')->nullable();
            $table->unsignedTinyInteger('final_level')->nullable();
            $table->foreignId('current_unit_id')->nullable()->constrained('tryout_units')->nullOnDelete();
            $table->unsignedSmallInteger('raw_score')->nullable();
            $table->unsignedSmallInteger('max_score')->nullable();
            $table->decimal('band', 3, 1)->nullable();
            $table->string('ai_status', 16)->nullable();
            $table->json('ai_assessment')->nullable();
            $table->string('ai_error')->nullable();
            $table->unsignedTinyInteger('ai_attempts')->default(0);
            $table->timestamp('ai_heartbeat_at')->nullable();
            $table->json('audio_plays')->nullable();
            $table->timestamps();

            $table->unique(['tryout_attempt_id', 'skill'], 'tryout_attempt_sections_attempt_skill_unique');
        });

        Schema::create('tryout_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tryout_attempt_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tryout_question_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->unsignedTinyInteger('level_at_answer');
            $table->text('response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->unsignedSmallInteger('word_count')->nullable();
            $table->boolean('over_word_limit')->default(false);
            $table->string('audio_path')->nullable();
            $table->timestamp('audio_deleted_at')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('ai_status', 16)->nullable();
            $table->json('ai_assessment')->nullable();
            $table->string('ai_error')->nullable();
            $table->unsignedTinyInteger('ai_attempts')->default(0);
            $table->decimal('band', 3, 1)->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->timestamps();

            $table->unique(['tryout_attempt_section_id', 'tryout_question_id'], 'tryout_answers_section_question_unique');
            $table->unique(['tryout_attempt_section_id', 'sequence'], 'tryout_answers_section_sequence_unique');
        });
    }

    /**
     * Remove tables left behind by an earlier run of this migration that
     * failed halfway (MySQL DDL is not transactional, so the first tables
     * stayed while the migration was never recorded). Such tables can only be
     * empty; a table that holds rows stops the migration instead of being dropped.
     *
     * @throws RuntimeException When a leftover table is not empty.
     */
    private function dropLeftoversOfFailedRun(): void
    {
        $tables = ['tryout_answers', 'tryout_attempt_sections', 'tryout_attempts'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Tabel {$table} sudah ada dan berisi data. Migration dihentikan agar data tidak terhapus; periksa secara manual.");
            }
        }

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tryout_answers');
        Schema::dropIfExists('tryout_attempt_sections');
        Schema::dropIfExists('tryout_attempts');
    }
};
