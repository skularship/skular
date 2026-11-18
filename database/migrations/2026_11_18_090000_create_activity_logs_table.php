<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the behaviour log: one row per tracked mentee action (see App\Enums\ActivityName).
     *
     * Rows are append-only and never updated. Anonymous visitors are kept
     * apart by a hashed session id; no IP address or user agent is stored.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_key', 64)->nullable();
            $table->string('name', 64)->index();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['user_id', 'name']);
        });
    }

    /**
     * Drop the behaviour log.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
