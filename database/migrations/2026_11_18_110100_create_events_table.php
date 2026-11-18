<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the events table (webinar, intensive class, personal mentoring).
     *
     * Times are stored in UTC and shown in WIB. location_url is the
     * Zoom/Meet link, shown only to registered mentees from H-1.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('type', 32);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('location_url')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('audience', 32)->default('semua');
            $table->string('invite_tag', 64)->nullable();
            $table->json('registration_questions')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->string('recording_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Drop the events table.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
