<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Optional card image of a scholarship (a Skular illustration or neutral
     * picture, never an organiser logo). Nullable and additive: scholarships
     * without an image render a branded placeholder.
     */
    public function up(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->string('image_path')
                ->nullable()
                ->after('link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
