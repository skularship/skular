<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the "Pratinjau gratis" flag: the one Pro event whose recording
     * Gratis mentees may watch as a preview. Defaults to false, so existing
     * events keep their current visibility.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_free_preview')->default(false)->after('recording_url');
        });
    }

    /**
     * Drop the flag.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('is_free_preview');
        });
    }
};
