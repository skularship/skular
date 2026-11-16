<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the log that stops a reminder from being sent twice.
     */
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scholarship_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('days_before');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['scholarship_application_id', 'days_before']);
        });
    }

    /**
     * Drop the reminder logs table.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};
