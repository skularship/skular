<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            // New modules start as drafts so unpublished work can never leak
            // into the mentee learning path by accident.
            $table->string('content_status')->default('draft')->after('type');

            $table->index('content_status');
        });

        // Everything that already exists is live for mentees today, so it is
        // backfilled as published — nobody loses access to current content.
        DB::table('modules')->update(['content_status' => 'published']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex(['content_status']);
            $table->dropColumn('content_status');
        });
    }
};
