<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed labels on a mentee, e.g. ["alumni_akademi_unggulan"].
     * Used for invitation-only events and to separate feedback data.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('tags')->nullable();
        });
    }

    /**
     * Drop the tags column.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
