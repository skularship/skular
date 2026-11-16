<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the audit table that keeps every raw Midtrans notification.
     */
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable()->index();
            $table->json('payload');
            $table->timestamps();
        });
    }

    /**
     * Drop the payment events table.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
