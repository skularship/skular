<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These composites follow the real read paths:
     * - curriculum resolution filters content_status + category (+ scholarship).
     * - the catalog and matching filters status and orders by closing_date.
     *
     * Both keep their existing single-column indexes; the composites just cover
     * the combined predicate instead of scanning the single-column result.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->index(
                ['content_status', 'category', 'scholarship_id'],
                'modules_curriculum_lookup_index',
            );
        });

        Schema::table('scholarships', function (Blueprint $table) {
            $table->index(
                ['status', 'closing_date'],
                'scholarships_status_closing_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex('modules_curriculum_lookup_index');
        });

        Schema::table('scholarships', function (Blueprint $table) {
            $table->dropIndex('scholarships_status_closing_index');
        });
    }
};
