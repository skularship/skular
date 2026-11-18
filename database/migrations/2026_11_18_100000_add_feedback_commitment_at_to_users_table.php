<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the mentee agreed to the free-access commitment (honest feedback at
     * the end of every module). Null = not agreed yet; modules stay closed.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('feedback_commitment_at')->nullable()->after('profile_completed_at');
        });
    }

    /**
     * Drop the commitment timestamp.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('feedback_commitment_at');
        });
    }
};
