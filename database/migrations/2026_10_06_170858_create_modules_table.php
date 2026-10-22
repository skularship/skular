<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('modul_order')->default(0);
            $table->text('summary_material')->nullable();
            $table->json('insights')->nullable();
            $table->longText('inti_material')->nullable();
            $table->string('type')->default('reading');
            $table->unsignedSmallInteger('duration')->nullable();
            $table->text('goal')->nullable();
            $table->string('link_video')->nullable();
            $table->longText('ai_knowledge')->nullable();
            $table->timestamps();

            $table->index(['fase_id', 'modul_order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
