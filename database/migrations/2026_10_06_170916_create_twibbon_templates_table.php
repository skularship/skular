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
        Schema::create('twibbon_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->string('font_path')->nullable();

            // Relative (0..1) text anchors so templates stay resolution independent.
            // e.g. [{"key":"user_name","x":0.5,"y":0.72,"font_size":48,"color":"#ffffff","align":"center"}]
            $table->json('placements')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('ordering')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'ordering']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twibbon_templates');
    }
};
