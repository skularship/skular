<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remember for which end date each expiry reminder was sent, so a renewal resets it by itself.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('reminded_7_for')->nullable()->after('active_until');
            $table->timestamp('reminded_1_for')->nullable()->after('reminded_7_for');
        });
    }

    /**
     * Remove the reminder columns.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['reminded_7_for', 'reminded_1_for']);
        });
    }
};
