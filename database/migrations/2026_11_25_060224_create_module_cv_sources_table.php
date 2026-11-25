<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Links a module to the "include in CV" practice forms whose answers the
     * mentee may open as a read-only reference on that module's Praktik step.
     * New table only: no existing data is touched. Index names are explicit
     * because MySQL limits identifiers to 64 characters.
     */
    public function up(): void
    {
        Schema::create('module_cv_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_form_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['module_id', 'practice_form_id'], 'module_cv_sources_module_form_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_cv_sources');
    }
};
