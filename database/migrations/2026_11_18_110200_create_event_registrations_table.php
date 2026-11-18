<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the event registrations: one row per mentee per event (unique).
     *
     * reminded_at records which reminders were sent ({"day": ts, "hour": ts})
     * so events:remind never sends one twice. The event cannot be deleted
     * while registrations exist (restrict); the rows go with the account.
     */
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->string('status', 32)->default('registered');
            $table->json('reminded_at')->nullable();
            $table->unsignedTinyInteger('feedback_score')->nullable();
            $table->text('feedback_text')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Drop the event registrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
