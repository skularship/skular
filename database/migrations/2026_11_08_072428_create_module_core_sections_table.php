<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The "Inti Materi" of a module as one or more titled columns. New table
     * only: the legacy modules.inti_material text stays untouched and is still
     * shown for modules that have no columns yet.
     */
    public function up(): void
    {
        Schema::create('module_core_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('subtitle');
            $table->longText('body');
            $table->unsignedInteger('ordering')->default(0);
            $table->timestamps();

            $table->index(['module_id', 'ordering']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_core_sections');
    }
};
