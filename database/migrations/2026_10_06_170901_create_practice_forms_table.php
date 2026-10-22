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
        Schema::create('practice_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('fields')->nullable();
            $table->unsignedSmallInteger('ordering')->default(0);
            $table->boolean('is_active')->default(true);

            // When enabled, the form title becomes a CV section populated by the
            // mentee's latest answers.
            $table->boolean('include_in_cv')->default(false);
            $table->unsignedSmallInteger('cv_ordering')->default(0);

            $table->timestamps();

            $table->index(['module_id', 'ordering']);
            $table->index(['include_in_cv', 'cv_ordering']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_forms');
    }
};
