<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional open question an admin sets for the module feedback; when
     * empty, a rotating default question is used (ModuleFeedbackService).
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->string('feedback_question')->nullable();
        });
    }

    /**
     * Drop the custom feedback question.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('feedback_question');
        });
    }
};
