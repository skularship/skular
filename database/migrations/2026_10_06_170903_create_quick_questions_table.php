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
        Schema::create('quick_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->boolean('answer')->default(true);
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('ordering')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['module_id', 'ordering']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_questions');
    }
};
