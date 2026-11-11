<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Which product tours (onboarding guides) a mentee already finished or
     * skipped, as {tour key: ISO timestamp}. Nullable and additive: existing
     * accounts simply see each tour once on their next visit.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('onboarding_tours')
                ->nullable()
                ->after('profile_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_tours');
        });
    }
};
