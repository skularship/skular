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
        Schema::table('modules', function (Blueprint $table) {
            // Curriculum classification: inti / template / perpustakaan.
            // The existing `type` column keeps describing the presentation
            // format (reading, video, practice, mixed) and is left untouched.
            $table->string('category')->default('inti')->after('type');

            // Only template modules point at a single scholarship.
            $table->foreignId('scholarship_id')
                ->nullable()
                ->after('category')
                ->constrained('scholarships')
                ->restrictOnDelete();

            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['scholarship_id']);
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'scholarship_id']);
        });
    }
};
