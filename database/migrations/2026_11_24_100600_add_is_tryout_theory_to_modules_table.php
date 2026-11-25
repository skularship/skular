<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Marks a module as Try Out theory: hidden from the Perpustakaan, refused
     * by the module player, and only readable (Pro) on the Try Out theory page.
     * Additive column with a false default, so every existing module keeps
     * behaving as before.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->boolean('is_tryout_theory')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('is_tryout_theory');
        });
    }
};
