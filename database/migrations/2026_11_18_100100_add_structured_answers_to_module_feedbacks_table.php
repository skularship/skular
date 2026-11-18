<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the mandatory end-of-module feedback fields. The old checklist
     * (ratings, suggestion) stays untouched for feedback sent before.
     */
    public function up(): void
    {
        Schema::table('module_feedbacks', function (Blueprint $table) {
            $table->unsignedTinyInteger('helpful_score')->nullable()->after('suggestion');
            $table->string('difficulty', 32)->nullable()->after('helpful_score');
            $table->string('open_question')->nullable()->after('difficulty');
            $table->text('open_answer')->nullable()->after('open_question');
            $table->text('low_score_reason')->nullable()->after('open_answer');
        });
    }

    /**
     * Drop the structured feedback fields.
     */
    public function down(): void
    {
        Schema::table('module_feedbacks', function (Blueprint $table) {
            $table->dropColumn(['helpful_score', 'difficulty', 'open_question', 'open_answer', 'low_score_reason']);
        });
    }
};
