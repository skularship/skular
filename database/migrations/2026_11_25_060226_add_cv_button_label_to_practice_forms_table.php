<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Short text for the "Tampilkan ..." button of a CV reference source
     * (for example "pengalamanku"). Nullable and additive: an empty value
     * falls back to the form title, so existing forms keep working.
     */
    public function up(): void
    {
        Schema::table('practice_forms', function (Blueprint $table) {
            $table->string('cv_button_label', 60)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_forms', function (Blueprint $table) {
            $table->dropColumn('cv_button_label');
        });
    }
};
