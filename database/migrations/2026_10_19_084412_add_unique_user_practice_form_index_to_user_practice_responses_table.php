<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The application treats user_practice_responses as one row per mentee and
     * practice form. Enforce that at the database level so two near-simultaneous
     * submissions can never create duplicate answers. Existing duplicates are
     * not deleted automatically: the migration stops with an actionable error
     * so an operator can reconcile the affected mentees first.
     */
    public function up(): void
    {
        $duplicates = DB::table('user_practice_responses')
            ->select('user_id', 'practice_form_id')
            ->groupBy('user_id', 'practice_form_id')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Found '.$duplicates->count().' duplicate user_id/practice_form_id pair(s) in user_practice_responses. '
                .'Reconcile those rows before running this migration; no data was changed.',
            );
        }

        Schema::table('user_practice_responses', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'practice_form_id'],
                'user_practice_responses_user_form_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_practice_responses', function (Blueprint $table) {
            $table->dropUnique('user_practice_responses_user_form_unique');
        });
    }
};
