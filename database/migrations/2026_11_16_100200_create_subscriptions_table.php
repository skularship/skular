<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the table holding one Pro subscription row per mentee.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('plan');
            $table->timestamp('active_until');
            $table->timestamps();
        });
    }

    /**
     * Drop the subscriptions table.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
