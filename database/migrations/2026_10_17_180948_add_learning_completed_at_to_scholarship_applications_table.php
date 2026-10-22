<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Learning completion is deliberately stored next to the application
     * instead of being derived only from checkpoints: it records that the
     * mentee has explicitly left this learning path, which is the trigger for
     * opening the next scholarship in their queue.
     */
    public function up(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->timestamp('learning_completed_at')->nullable()->after('result_reported_at');

            $table->index(['user_id', 'learning_completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'learning_completed_at']);
            $table->dropColumn('learning_completed_at');
        });
    }
};
