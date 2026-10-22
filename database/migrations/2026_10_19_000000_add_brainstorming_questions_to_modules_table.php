<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Existing modules keep working with an empty configuration. The column is
     * nullable and additive, so no existing learning data is touched.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->json('brainstorming_questions')
                ->nullable()
                ->after('ai_knowledge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('brainstorming_questions');
        });
    }
};
