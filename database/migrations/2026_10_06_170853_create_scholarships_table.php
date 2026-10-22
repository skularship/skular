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
        Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->string('scholarship_name');
            $table->string('institution')->nullable();
            $table->string('level');
            $table->string('location')->nullable();
            $table->string('scope')->nullable();
            $table->boolean('sktm_require')->default(false);
            $table->decimal('minimum_value', 3, 2)->nullable();
            $table->unsignedTinyInteger('maximum_age')->nullable();
            $table->date('opening_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->unsignedInteger('estimated_quota')->nullable();
            $table->string('link')->nullable();
            $table->date('verification_date')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['status', 'level']);
            $table->index('closing_date');
            $table->index('scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};
