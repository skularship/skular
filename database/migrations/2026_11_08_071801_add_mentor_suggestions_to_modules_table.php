<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Suggested questions the curriculum team shows inside the AI Mentor chat.
     * Nullable and additive: existing modules simply fall back to suggestions
     * derived from their own content.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->json('mentor_suggestions')
                ->nullable()
                ->after('brainstorming_questions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('mentor_suggestions');
        });
    }
};
