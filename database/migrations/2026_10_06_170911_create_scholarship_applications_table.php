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
        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scholarship_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('selected');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('selected_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            // Private disk path — never exposed publicly.
            $table->string('submission_proof')->nullable();
            $table->text('submission_note')->nullable();
            $table->timestamp('submission_confirmed_at')->nullable();

            $table->string('result')->nullable();
            $table->timestamp('result_reported_at')->nullable();

            // Reserved for the future pricing/discount feature.
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'scholarship_id']);
            $table->index(['user_id', 'is_primary']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_applications');
    }
};
