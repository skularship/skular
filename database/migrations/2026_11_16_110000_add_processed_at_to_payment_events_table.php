<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the time a stored notification was applied, so stuck jobs are easy to find.
     */
    public function up(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('payload');
        });
    }

    /**
     * Remove the processed_at column.
     */
    public function down(): void
    {
        Schema::table('payment_events', function (Blueprint $table) {
            $table->dropColumn('processed_at');
        });
    }
};
