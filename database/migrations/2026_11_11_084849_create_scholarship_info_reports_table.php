<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mentee reports of inaccurate scholarship data ("Informasi Tidak
     * Akurat?"). Reports outlive the reporting account: user_id and
     * reviewed_by are nulled instead of cascading, and a scholarship with
     * reports cannot be deleted (restrict).
     */
    public function up(): void
    {
        Schema::create('scholarship_info_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scholarship_id')->constrained()->restrictOnDelete();
            $table->string('field', 32);
            $table->text('suggested_value');
            $table->string('source_url', 2048)->nullable();
            $table->text('note')->nullable();
            $table->string('status', 16)->default('new');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['scholarship_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_info_reports');
    }
};
